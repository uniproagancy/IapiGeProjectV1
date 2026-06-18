<?php

namespace App\Jobs;

use App\Models\Elite\EliteProduct;
use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductCategory;
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

class CreateEliteProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    private const SUPPLIER_ID          = 11;
    private const DEFAULT_BRAND_ID     = 1;
    private const FALLBACK_CATEGORY_ID = 203;
    private const SHORT_SPEC_LIMIT     = 5;

    public function __construct(
        public array $productData,
        public array $availabilityInStores,
        public int   $eliteProductId
    ) {}

    public function handle(): void
    {
        try {
            $product = $this->productData;
            $barCode = (string) ($product['barCode'] ?? '');
            $name    = trim((string) ($product['name'] ?? ''));

            if (!$barCode || !$name) {
                Log::warning("⛔ Elite: name/barCode ცარიელია");
                return;
            }

            // ფასი
            $regularPrice  = (float) ($product['price'] ?? 0);
            $previousPrice = (float) ($product['previousPrice'] ?? 0);
            $discountPrice = 0.0;

            if ($previousPrice > 0 && $previousPrice > $regularPrice) {
                $discountPrice = $regularPrice;
                $regularPrice  = $previousPrice;
            }

            if ($regularPrice <= 0) {
                Log::warning("⛔ Elite: ფასი 0", ['barCode' => $barCode]);
                return;
            }

            // კატეგორია — Alta-ს მსგავსად სახელით
            $categoryId = $this->getCategoryId($product);

            // ბრენდი — Alta-ს მსგავსად translations-ით
            $brandId = $this->getBrandId($product);

            // Stock — Alta-ს მსგავსად availabilityInStores-ით
            $quantity = (int) ($product['storageQuantity'] ?? 0);
            $inStock  = $this->checkTbilisiStock($this->availabilityInStores) ? 1 : 0;

            // სურათები
            $images = [];
            if (!empty($product['imageUrl'])) {
                $images[] = $product['imageUrl'];
            }
            if (!empty($product['images']) && is_array($product['images'])) {
                $images = array_merge($images, $product['images']);
            }
            $images = array_values(array_unique(array_filter($images)));

            // Short specs
            $shortSpecs = $this->extractShortSpecs($product);

            // ============ Product upsert ============
            $existing = Product::where('sku', $barCode)->first();
            $isNew    = !$existing;

            if ($isNew) {
                $existing = Product::create([
                    'sku'           => $barCode,
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

                Log::info("➕ Elite: ახალი პროდუქტი", ['sku' => $barCode, 'id' => $existing->id]);
            } else {
                $existing->update([
                    'quantity' => $inStock ? max($quantity, 1) : 0,
                    'in_stock' => $inStock,
                    'show'     => $inStock,
                ]);

                Log::info("🔄 Elite: განახლდა", ['sku' => $barCode, 'id' => $existing->id]);
            }

            // Translation
            ProductTranslation::updateOrCreate(
                ['product_id' => $existing->id, 'locale' => 'ka'],
                [
                    'title'       => $name,
                    'slug'        => Str::slug($name) . '-' . $existing->id,
                    'description' => trim((string) ($product['description'] ?? '')),
                ]
            );

            // Price
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

            // Short specs (მხოლოდ ახალ პროდუქტზე)
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

            // Main image (მხოლოდ ახალ პროდუქტზე)
            if ($isNew && !empty($images)) {
                $localPath = $this->downloadImage($images[0], $existing->id);
                if ($localPath) {
                    $existing->update(['main_image' => $localPath]);
                    Log::info("📸 Elite: სურათი შენახულია", ['sku' => $barCode]);
                }
            }

            // EliteProduct — synced
            EliteProduct::where('id', $this->eliteProductId)->update([
                'synced'     => true,
                'product_id' => $existing->id,
            ]);

        } catch (\Throwable $e) {
            Log::error("❌ Elite Import შეცდომა", [
                'barCode' => $this->productData['barCode'] ?? null,
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);
            throw $e;
        }
    }

    // ============================================
    // Category — Alta-ს მსგავსად სახელით ძებნა
    // ============================================

    private function getCategoryId(array $product): int
    {
        $categoryName       = $product['categoryName'] ?? null;
        $parentCategoryName = $product['parentCategoryName'] ?? null;

        if ($categoryName) {
            $category = ProductCategory::where('elite_category_name', $categoryName)->first();
            if ($category) return $category->id;
        }

        if ($parentCategoryName) {
            $category = ProductCategory::where('elite_category_name', $parentCategoryName)->first();
            if ($category) return $category->id;
        }

        Log::info("⚠️ Elite: კატეგორია არ არის მაპავი", [
            'categoryName'       => $categoryName,
            'parentCategoryName' => $parentCategoryName,
        ]);

        return self::FALLBACK_CATEGORY_ID;
    }

    // ============================================
    // Brand — Alta-ს მსგავსად translations-ით ძებნა
    // ============================================

    private function getBrandId(array $product): int
    {
        try {
            // 1. brandName field-იდან
            $brandName = $product['brandName'] ?? null;

            // 2. specificationGroup-დან fallback
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

        } catch (\Throwable $e) {
            Log::warning("⚠️ Elite: ბრენდი ვერ მოიძებნა", ['error' => $e->getMessage()]);
            return self::DEFAULT_BRAND_ID;
        }
    }

    // ============================================
    // Stock — Alta-ს მსგავსად Tbilisi შემოწმება
    // ============================================

    private function checkTbilisiStock(array $availability): bool
    {
        if (empty($availability)) {
            return false;
        }

        return collect($availability)
            ->where('city', 'Tbilisi')
            ->contains(fn ($store) => $store['inStock'] === true);
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

        } catch (\Throwable $e) {
            Log::warning("⚠️ Elite: სურათი ვერ ჩამოიტვირთა", ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }
}