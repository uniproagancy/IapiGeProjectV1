<?php

namespace App\Jobs;

use App\Models\EliteProduct;
use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductFullSpecificationSection;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductShortSpecification;
use App\Models\Product\ProductTranslation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class EliteProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    private const SUPPLIER_ID          = 11;
    private const DEFAULT_BRAND_ID     = 1;
    private const FALLBACK_CATEGORY_ID = 204;
    private const SHORT_SPEC_LIMIT     = 5;

    public function __construct(
        protected array $productData,
        protected array $availabilityInStores = []
    ) {}

    public function handle(): void
    {
        try {
            $barCode = (string) ($this->productData['barCode'] ?? '');
            $name    = trim((string) ($this->productData['name'] ?? ''));

            if (!$barCode || !$name) {
                Log::warning("⛔ Elite: name/barCode ცარიელია");
                return;
            }

            // ✅ BarCode არის Excel-დან ატვირთულ სიაში?
            $eliteProduct = EliteProduct::where('bar_code', $barCode)
                ->where('synced', false)
                ->first();

            if (!$eliteProduct) {
                return;
            }

            // ფასი
            $regularPrice  = (float) ($this->productData['price'] ?? 0);
            $previousPrice = (float) ($this->productData['previousPrice'] ?? 0);
            $discountPrice = 0.0;

            if ($previousPrice > 0 && $previousPrice > $regularPrice) {
                $discountPrice = $regularPrice;
                $regularPrice  = $previousPrice;
            }

            if ($regularPrice <= 0) {
                Log::warning("⛔ Elite: ფასი 0", ['barCode' => $barCode]);
                return;
            }

            $categoryId = $this->getCategoryId($this->productData);
            $brandId    = $this->getBrandId($this->productData);

            $quantity = (int) ($this->productData['storageQuantity'] ?? 0);
            $inStock  = !empty($this->productData['isInStock']) ? 1 : 0;

            $images = [];
            if (!empty($this->productData['imageUrl'])) {
                $images[] = $this->productData['imageUrl'];
            }
            if (!empty($this->productData['images']) && is_array($this->productData['images'])) {
                $images = array_merge($images, $this->productData['images']);
            }
            $images = array_values(array_unique(array_filter($images)));

            $shortSpecs = $this->extractShortSpecs($this->productData);

            // ============ Product upsert ============
            $sku      = 'ELITE-' . $barCode;
            $existing = Product::where('sku', $sku)->first();
            $isNew    = !$existing;

            if ($isNew) {
                $existing = Product::create([
                    'sku'           => $sku,
                    'supplier_id'   => self::SUPPLIER_ID,
                    'brand_id'      => $brandId,
                    'category_id'   => $categoryId,
                    'quantity'      => $inStock ? max($quantity, 1) : 0,
                    'in_stock'      => $inStock,
                    'show'          => $inStock,
                    'active'        => 1,
                    'main_image'    => null,
                    'update_lock'   => 0,
                    'taxonomy_lock' => 0,
                ]);

                Log::info("➕ Elite: ახალი პროდუქტი", ['sku' => $sku, 'id' => $existing->id]);
            } else {
                // განახლება მხოლოდ თუ update_lock=0
                if (!$existing->update_lock) {
                    $existing->update([
                        'quantity' => $inStock ? max($quantity, 1) : 0,
                        'in_stock' => $inStock,
                        'show'     => $inStock,
                    ]);

                    Log::info("🔄 Elite: განახლდა", ['sku' => $sku, 'id' => $existing->id]);
                } else {
                    Log::info("🔒 Elite: ჩაკეტილია, გამოტოვება", ['sku' => $sku]);
                }
            }

            // Translation
            if ($isNew || !$existing->update_lock) {
                ProductTranslation::updateOrCreate(
                    ['product_id' => $existing->id, 'locale' => 'ka'],
                    [
                        'title'       => $name,
                        'slug'        => Str::slug($name) . '-' . $existing->id,
                        'description' => trim((string) ($this->productData['description'] ?? '')),
                    ]
                );
            }

            // Price
            if ($isNew || !$existing->update_lock) {
                ProductPrice::updateOrCreate(
                    ['product_id' => $existing->id],
                    [
                        'dealer_price'     => $regularPrice,
                        'regular_price'    => $regularPrice,
                        'discount_price'   => $discountPrice,
                        'discount_percent' => $discountPrice > 0
                            ? (int) round((($regularPrice - $discountPrice) / $regularPrice) * 100)
                            : 0,
                    ]
                );
            }

            // Short specs — მხოლოდ ახალ პროდუქტზე
            if ($isNew && !empty($shortSpecs)) {
                $sortOrder = 0;
                foreach ($shortSpecs as $key => $value) {
                    ProductShortSpecification::create([
                        'product_id' => $existing->id,
                        'locale'     => 'ka',
                        'name'       => $key,
                        'value'      => $value,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            // Full specs — ახალზეც და update-ზეც (update_lock=0 მაშინ)
            if ($isNew || !$existing->update_lock) {
                $this->saveFullSpecs($existing->id, $this->productData);
            }

            // Main image — მხოლოდ ახალ პროდუქტზე
            if ($isNew && !empty($images)) {
                $localPath = $this->downloadImage($images[0], $existing->id);
                if ($localPath) {
                    $existing->update(['main_image' => $localPath]);
                }
            }

            // EliteProduct → synced
            $eliteProduct->update([
                'synced'     => true,
                'product_id' => $existing->id,
            ]);

        } catch (Exception $e) {
            Log::error("❌ Elite Import შეცდომა", [
                'barCode' => $this->productData['barCode'] ?? null,
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);
            throw $e;
        }
    }

    // ============================================
    // Full Specifications — specificationGroup-დან
    // ============================================

    private function saveFullSpecs(int $productId, array $product): void
    {
        if (empty($product['specificationGroup']) || !is_array($product['specificationGroup'])) {
            return;
        }

        // წინა specs წაშლა და ახლის შენახვა
        $existingSections = ProductFullSpecificationSection::where('product_id', $productId)->get();
        foreach ($existingSections as $section) {
            $section->list()->delete();
            $section->delete();
        }

        foreach ($product['specificationGroup'] as $group) {
            $groupName = trim((string) ($group['groupName'] ?? ''));
            $specs     = $group['specifications'] ?? [];

            if (!$groupName || empty($specs)) {
                continue;
            }

            $section = ProductFullSpecificationSection::create([
                'product_id' => $productId,
                'name'       => $groupName,
            ]);

            foreach ($specs as $spec) {
                $specName  = trim((string) ($spec['specificationName'] ?? ''));
                $specValue = trim((string) ($spec['specificationMeaning'] ?? ''));

                if (!$specName || !$specValue) {
                    continue;
                }

                $section->list()->create([
                    'name'   => $specName,
                    'value'  => $specValue,
                    'filter' => 0,
                ]);
            }
        }
    }

    // ============================================
    // Category
    // ============================================

    private function getCategoryId(array $productData): int
    {
        $categoryName = $productData['categoryName'] ?? null;

        if ($categoryName) {
            $category = ProductCategory::whereRaw(
                'LOWER(TRIM(elite_category_name)) = ?',
                [mb_strtolower(trim($categoryName))]
            )->first();

            if ($category) {
                Log::info("✅ Elite category mapped: '{$categoryName}' → category_id={$category->id}");
                return $category->id;
            }
        }

        Log::warning("⚠️ Elite category not mapped: categoryName='{$categoryName}'");
        return self::FALLBACK_CATEGORY_ID;
    }

    // ============================================
    // Brand
    // ============================================

    private function getBrandId(array $product): int
    {
        try {
            $brandName = $product['brandName'] ?? null;

            if (empty($brandName)) {
                foreach ($product['specificationGroup'] ?? [] as $group) {
                    foreach ($group['specifications'] ?? [] as $spec) {
                        if (in_array($spec['specificationName'], ['ბრენდი', 'Brand', 'Бренд'])) {
                            $brandName = $spec['specificationMeaning'] ?? null;
                            break 2;
                        }
                    }
                }
            }

            if (empty($brandName)) {
                return self::DEFAULT_BRAND_ID;
            }

            return Cache::remember(
                'elite_brand_' . md5($brandName),
                now()->addHours(24),
                function () use ($brandName) {
                    $brand = ProductBrand::whereHas(
                        'translations',
                        fn ($q) => $q->where('title', 'like', $brandName)
                    )->first();

                    return $brand->id ?? self::DEFAULT_BRAND_ID;
                }
            );

        } catch (Exception $e) {
            Log::warning("⚠️ Elite: ბრენდი ვერ მოიძებნა", ['error' => $e->getMessage()]);
            return self::DEFAULT_BRAND_ID;
        }
    }

    // ============================================
    // Short Specs
    // ============================================

    private function extractShortSpecs(array $product): array
    {
        $specs = [];

        if (!empty($product['mainSpecification']) && is_array($product['mainSpecification'])) {
            foreach ($product['mainSpecification'] as $spec) {
                $key   = trim((string) ($spec['specificationName'] ?? ''));
                $value = trim((string) ($spec['specificationMeaning'] ?? ''));
                if ($key && $value && !isset($specs[$key])) {
                    $specs[$key] = $value;
                    if (count($specs) >= self::SHORT_SPEC_LIMIT) return $specs;
                }
            }
        }

        if (!empty($product['specificationGroup']) && is_array($product['specificationGroup'])) {
            foreach ($product['specificationGroup'] as $group) {
                if (empty($group['specifications'])) continue;
                foreach ($group['specifications'] as $spec) {
                    $key   = trim((string) ($spec['specificationName'] ?? ''));
                    $value = trim((string) ($spec['specificationMeaning'] ?? ''));
                    if ($key && $value && !isset($specs[$key])) {
                        $specs[$key] = $value;
                        if (count($specs) >= self::SHORT_SPEC_LIMIT) return $specs;
                    }
                }
            }
        }

        return $specs;
    }

    // ============================================
    // Image Download
    // ============================================

    private function downloadImage(string $url, int $productId): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
                    'Referer'    => 'https://ee.ge/',
                ])
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg');
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $ext = 'jpg';
            }

            $path = "uploads/products/{$productId}/main_" . time() . ".{$ext}";
            Storage::disk('public')->put($path, $response->body());

            return $path;

        } catch (Exception $e) {
            Log::warning("⚠️ Elite: სურათი ვერ ჩამოიტვირთა", [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error("🚨 EliteProductJob permanently failed", [
            'barCode' => $this->productData['barCode'] ?? null,
            'error'   => $exception->getMessage(),
        ]);
    }
}