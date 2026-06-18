<?php

namespace App\Jobs;

use App\Models\EliteProduct;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductShortSpecification;
use App\Models\Product\ProductTranslation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
    private const BRAND_ID             = 1;
    private const FALLBACK_CATEGORY_ID = 203;
    private const SHORT_SPEC_LIMIT     = 5;

    public function __construct(
        public array $productData,
        public int $eliteProductId
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

            // SKU = BarCode (User-ის გადაწყვეტილებით)
            $sku = $barCode;

            // ფასი — JSON-დან როგორც არის
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

            // კატეგორია mapping-დან (db_product_categories.elite_category_id)
            $categoryId = $this->resolveCategoryId(
                (int) ($product['categoryId'] ?? 0),
                (string) ($product['categoryName'] ?? '')
            );

            // მარაგი
            $quantity = (int) ($product['storageQuantity'] ?? 0);
            $inStock  = $quantity > 0 ? 1 : 0;

            // აღწერა
            $description = trim((string) ($product['description'] ?? ''));

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
            $existingProduct = Product::where('sku', $sku)->first();
            $isNew = !$existingProduct;

            if ($isNew) {
                $existingProduct = Product::create([
                    'sku'           => $sku,
                    'supplier_id'   => self::SUPPLIER_ID,
                    'brand_id'      => self::BRAND_ID,
                    'category_id'   => $categoryId,
                    'quantity'      => $inStock ? max($quantity, 1) : 0,
                    'in_stock'      => $inStock,
                    'show'          => $inStock,
                    'active'        => 1,
                    'main_image'    => null,
                    'update_lock'   => 0,
                    'taxonomy_lock' => 0,
                ]);

                Log::info("➕ Elite: ახალი პროდუქტი", ['sku' => $sku, 'id' => $existingProduct->id]);
            } else {
                // Update მხოლოდ მარაგი/ხილვადობა
                $existingProduct->update([
                    'quantity' => $inStock ? max($quantity, 1) : 0,
                    'in_stock' => $inStock,
                    'show'     => $inStock,
                ]);

                Log::info("🔄 Elite: განახლდა", ['sku' => $sku, 'id' => $existingProduct->id]);
            }

            // Translation (ka)
            ProductTranslation::updateOrCreate(
                ['product_id' => $existingProduct->id, 'locale' => 'ka'],
                [
                    'title'       => $name,
                    'slug'        => Str::slug($name) . '-' . $existingProduct->id,
                    'description' => $description,
                ]
            );

            // Price
            ProductPrice::updateOrCreate(
                ['product_id' => $existingProduct->id],
                [
                    'dealer_price'     => $regularPrice,
                    'regular_price'    => $regularPrice,
                    'discount_price'   => $discountPrice,
                    'discount_percent' => $discountPrice > 0
                        ? (int) round((($regularPrice - $discountPrice) / $regularPrice) * 100)
                        : 0,
                ]
            );

            // Short specs (only on create)
            if ($isNew && !empty($shortSpecs)) {
                $sortOrder = 0;
                foreach ($shortSpecs as $key => $value) {
                    ProductShortSpecification::create([
                        'product_id' => $existingProduct->id,
                        'locale'     => 'ka',
                        'name'       => $key,
                        'value'      => $value,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            // Main image
            if (empty($existingProduct->main_image) && !empty($images)) {
                $localPath = $this->downloadImage($images[0], $existingProduct->id);
                if ($localPath) {
                    $existingProduct->update(['main_image' => $localPath]);
                    Log::info("📸 Elite: main_image შენახულია", ['sku' => $sku, 'path' => $localPath]);
                }
            }

            // EliteProduct-ი მონიშნე synced-ად
            EliteProduct::where('id', $this->eliteProductId)->update([
                'synced'     => true,
                'product_id' => $existingProduct->id,
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

    /**
     * ee.ge categoryId → iapi category_id mapping
     * (db_product_categories.elite_category_id)
     */
    private function resolveCategoryId(int $eliteCategoryId, string $eliteCategoryName): int
    {
        if (!$eliteCategoryId) {
            return self::FALLBACK_CATEGORY_ID;
        }

        $category = ProductCategory::where('elite_category_id', $eliteCategoryId)->first();

        if ($category) {
            // categoryName განვაახლოთ თუ ცარიელია ან განსხვავდება — admin გვერდისთვის
            if ($eliteCategoryName && $category->elite_category_name !== $eliteCategoryName) {
                $category->update(['elite_category_name' => $eliteCategoryName]);
            }
            return $category->id;
        }

        // არ არსებობს mapping — log რომ ნახე და ხელით mapping გაკეთდეს
        Log::info("📋 Elite: ახალი დაუმაპავი კატეგორია", [
            'elite_category_id'   => $eliteCategoryId,
            'elite_category_name' => $eliteCategoryName,
        ]);

        return self::FALLBACK_CATEGORY_ID;
    }

    /**
     * mainSpecification + specificationGroup → short specs (max 5)
     */
    private function extractShortSpecs(array $product): array
    {
        $specs = [];

        // 1. mainSpecification
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

        // 2. specificationGroup-ის ყველა group-დან
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

    /**
     * სურათის ჩამოტვირთვა local storage-ში
     */
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
                Log::warning("⚠️ Elite: სურათი ვერ ჩამოიტვირთა (HTTP)", [
                    'url'    => $url,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $ext = strtolower($ext);
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $ext = 'jpg';
            }

            $filename = 'main_' . time() . '.' . $ext;
            $path = "uploads/products/{$productId}/{$filename}";

            Storage::disk('public')->put($path, $response->body());

            return $path;

        } catch (\Throwable $e) {
            Log::warning("⚠️ Elite: სურათი ვერ ჩამოიტვირთა (exception)", [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}