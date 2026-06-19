<?php

namespace App\Jobs;

use App\Models\EliteProduct;
use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductBrandTranslation;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductFullSpecificationItem;
use App\Models\Product\ProductFullSpecificationSection;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductShortSpecification;
use App\Models\Product\ProductTranslation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class EliteProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $productData         = [];
    protected array $productAvailability = [];

    public int $tries             = 3;
    public int $timeout           = 300;
    public int $maxExceptions     = 3;
    public int $backoffMultiplier = 2;

    private const SUPPLIER_ID          = 11;
    private const DEFAULT_BRAND_ID     = 1;
    private const FALLBACK_CATEGORY_ID = 204;
    private const CACHE_DURATION_BRAND = 24 * 60;
    private const MAX_IMAGE_SIZE       = 5 * 1024 * 1024;
    private const SHORT_SPEC_LIMIT     = 5;

    public function __construct(array $productData = [], array $productAvailability = [])
    {
        $this->productData         = $productData;
        $this->productAvailability = $productAvailability;
    }

    public function handle(): void
    {
        try {
            if (empty($this->productData) || empty($this->productData['barCode'])) {
                Log::warning('⚠️ EliteProductJob: productData is empty or missing barCode');
                return;
            }

            Log::info("🔄 Processing Elite product: {$this->productData['barCode']}", [
                'attempt' => $this->attempts(),
            ]);

            $this->saveProduct($this->productData, $this->productAvailability);

            Log::info("✅ Elite product saved: {$this->productData['barCode']}");

        } catch (Exception $e) {
            $barCode = $this->productData['barCode'] ?? 'unknown';
            Log::error("❌ Error processing Elite product {$barCode}: {$e->getMessage()}", [
                'attempt' => $this->attempts(),
                'trace'   => $e->getTraceAsString(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->getRetryDelay());
            } else {
                Log::critical("🚫 Elite job permanently failed: {$barCode}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function getRetryDelay(): int
    {
        return pow($this->backoffMultiplier, $this->attempts()) * 60;
    }

    public function failed(Exception $exception): void
    {
        $barCode = $this->productData['barCode'] ?? 'unknown';
        Log::error("🚨 Elite job permanently failed for product {$barCode}", [
            'error'    => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    public function saveProduct(array $productData, array $productAvailability): void
    {
        $barCode = (string) ($productData['barCode'] ?? '');

        if (!$barCode) {
            Log::warning('⚠️ Elite: barCode ცარიელია');
            return;
        }

        // ✅ BarCode ჩვენს სიაშია?
        $eliteProduct = EliteProduct::where('bar_code', $barCode)->first();

        if (!$eliteProduct) {
            return;
        }

        $sku      = 'ELITE-' . $barCode;
        $existing = Product::where('sku', $sku)->first();
        $exists   = (bool) $existing;

        if ($exists) {
            $this->updateExistingProduct($productData, $sku, $eliteProduct);
        } else {
            $this->createNewProduct($productData, $sku, $eliteProduct);
        }
    }

    // ============================================
    // Stock
    // ============================================

    private function checkTbilisiStock(array $availability): bool
    {
        if (empty($availability)) {
            return false;
        }

        return collect($availability)
            ->where('city', 'თბილისი')
            ->contains(fn ($store) => $store['inStock'] === true);
    }

    // ============================================
    // Update
    // ============================================

    private function updateExistingProduct(array $productData, string $sku, EliteProduct $eliteProduct): void
    {
        try {
            $product = Product::where('sku', $sku)->firstOrFail();

            DB::transaction(function () use ($product, $productData, $sku, $eliteProduct) {
                $productPrice  = (float) ($productData['previousPrice'] ?? $productData['price'] ?? 0);
                $discountPrice = $productData['previousPrice'] ? (float) $productData['price'] : null;

                ProductPrice::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'dealer_price'     => $productPrice,
                        'regular_price'    => $productPrice,
                        'discount_price'   => $discountPrice,
                        'discount_percent' => $productData['discountPercent'] ?? 0,
                    ]
                );

                $inStock  = !empty($productData['isInStock']) ? 1 : 0;
                $quantity = (int) ($productData['storageQuantity'] ?? 0);

                $updateData = [
                    'quantity' => $inStock ? max($quantity, 1) : 0,
                    'in_stock' => $inStock,
                    'show'     => $inStock,
                    'active'   => $inStock,
                ];

                if (!$product->taxonomy_lock) {
                    $updateData['category_id'] = $this->getCategoryId($productData);
                    $updateData['brand_id']    = $this->getBrandId($productData);
                } else {
                    Log::info("🏷️ Elite: taxonomy locked, category/brand უცვლელი — {$sku}");
                }

                $product->update($updateData);

                if (!empty($productData['description'])) {
                    ProductTranslation::where('product_id', $product->id)
                        ->where('locale', 'ka')
                        ->update(['description' => $this->sanitizeString($productData['description'])]);
                }

                ProductShortSpecification::where('product_id', $product->id)->forceDelete();
                $this->createShortSpecifications($product, $productData);

                $sectionIds = ProductFullSpecificationSection::where('product_id', $product->id)->pluck('id');
                ProductFullSpecificationItem::whereIn('section_id', $sectionIds)->forceDelete();
                ProductFullSpecificationSection::where('product_id', $product->id)->forceDelete();
                $this->createFullSpecifications($product, $productData);

                $eliteProduct->update([
                    'synced'     => true,
                    'product_id' => $product->id,
                ]);

                Log::info("🔁 Updated Elite product: {$product->id}");
            });

        } catch (Exception $e) {
            Log::error("❌ Error updating Elite product {$sku}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Create
    // ============================================

    private function createNewProduct(array $productData, string $sku, EliteProduct $eliteProduct): void
    {
        DB::transaction(function () use ($productData, $sku, $eliteProduct) {
            try {
                $brandId    = $this->getBrandId($productData);
                $categoryId = $this->getCategoryId($productData);

                $inStock  = !empty($productData['isInStock']) ? 1 : 0;
                $quantity = (int) ($productData['storageQuantity'] ?? 0);

                $product = Product::create([
                    'brand_id'      => $brandId,
                    'category_id'   => $categoryId,
                    'sku'           => $sku,
                    'supplier_id'   => self::SUPPLIER_ID,
                    'main_image'    => null,
                    'active'        => 1,
                    'quantity'      => $inStock ? max($quantity, 1) : 0,
                    'in_stock'      => $inStock,
                    'show'          => $inStock,
                    'update_lock'   => 0,
                    'taxonomy_lock' => 0,
                ]);

                $this->createPrice($product, $productData);
                $this->createTranslations($product, $productData);
                $this->createFullSpecifications($product, $productData);
                $this->createShortSpecifications($product, $productData);
                $this->downloadAndSaveImages($product, $productData);

                $eliteProduct->update([
                    'synced'     => true,
                    'product_id' => $product->id,
                ]);

                Log::info("✨ Created new Elite product: {$product->id}, category: {$categoryId}, brand: {$brandId}");

            } catch (Exception $e) {
                Log::error("❌ Error creating Elite product: {$e->getMessage()}");
                throw $e;
            }
        });
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

    private function getBrandId(array $productData): int
    {
        try {
            $brandName = $productData['brandName'] ?? null;

            if (empty($brandName)) {
                foreach ($productData['specificationGroup'] ?? [] as $group) {
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

            $normalized = mb_strtolower(trim($brandName));

            return Cache::remember(
                'elite_brand_' . md5($normalized),
                now()->addMinutes(self::CACHE_DURATION_BRAND),
                function () use ($brandName, $normalized) {
                    $brand = ProductBrand::whereHas('translations',
                        fn ($q) => $q->whereRaw('LOWER(TRIM(title)) = ?', [$normalized])
                    )->first();

                    if ($brand) {
                        Log::info("✅ Elite brand found: '{$brandName}' → brand_id={$brand->id}");
                        return $brand->id;
                    }

                    // Alta-ს მსგავსად: ბრენდი ვერ მოიძებნა → ახალი შევქმნათ
                    $newBrand = ProductBrand::create([
                        'active' => 1,
                        'show'   => 1,
                    ]);

                    ProductBrandTranslation::create([
                        'product_brand_id' => $newBrand->id,
                        'locale'           => 'ka',
                        'title'            => $brandName,
                        'slug'             => Str::slug($brandName) . '-' . $newBrand->id,
                    ]);

                    ProductBrandTranslation::create([
                        'product_brand_id' => $newBrand->id,
                        'locale'           => 'en',
                        'title'            => $brandName,
                        'slug'             => Str::slug($brandName) . '-' . $newBrand->id . '-en',
                    ]);

                    Log::info("✨ Elite: New brand created: '{$brandName}', id={$newBrand->id}");

                    return $newBrand->id;
                }
            );

        } catch (Exception $e) {
            Log::warning("⚠️ Error finding/creating Elite brand: {$e->getMessage()}");
            return self::DEFAULT_BRAND_ID;
        }
    }

    // ============================================
    // Price
    // ============================================

    private function createPrice(Product $product, array $productData): void
    {
        try {
            $productPrice  = (float) ($productData['previousPrice'] ?? $productData['price'] ?? 0);
            $discountPrice = $productData['previousPrice'] ? (float) $productData['price'] : null;

            ProductPrice::create([
                'product_id'       => $product->id,
                'dealer_price'     => $productPrice,
                'regular_price'    => $productPrice,
                'discount_price'   => $discountPrice,
                'discount_percent' => $productData['discountPercent'] ?? 0,
            ]);

        } catch (Exception $e) {
            Log::error("❌ Error creating price for Elite product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Translations
    // ============================================

    private function createTranslations(Product $product, array $productData): void
    {
        try {
            $productName = $this->sanitizeString($productData['name']) ?: 'Unnamed Product';
            $baseSlug    = Str::slug($productName) . "-{$product->id}";

            foreach (['ka', 'en', 'ru'] as $locale) {
                ProductTranslation::create([
                    'product_id'  => $product->id,
                    'locale'      => $locale,
                    'title'       => $productName,
                    'slug'        => $baseSlug,
                    'description' => $locale === 'ka'
                        ? $this->sanitizeString($productData['description'] ?? null)
                        : null,
                    'keywords'    => null,
                ]);
            }

        } catch (Exception $e) {
            Log::error("❌ Error creating translations for Elite product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Full Specifications — Alta-ს ზუსტი პატერნი
    // ============================================

    private function createFullSpecifications(Product $product, array $productData): void
    {
        try {
            if (empty($productData['specificationGroup'])) {
                return;
            }

            foreach ($productData['specificationGroup'] as $specificationGroup) {
                if (empty($specificationGroup['groupName'])) {
                    continue;
                }

                $section = ProductFullSpecificationSection::create([
                    'product_id' => $product->id,
                    'name'       => $specificationGroup['groupName'],
                ]);

                if (!empty($specificationGroup['specifications'])) {
                    foreach ($specificationGroup['specifications'] as $spec) {
                        if (empty($spec['specificationName'])) {
                            continue;
                        }

                        ProductFullSpecificationItem::create([
                            'section_id' => $section->id,
                            'name'       => $this->sanitizeString($spec['specificationName']),
                            'value'      => $this->sanitizeString($spec['specificationMeaning'] ?? null),
                            'filter'     => 0,
                        ]);
                    }
                }
            }

        } catch (Exception $e) {
            Log::error("❌ Error creating full specs for Elite product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Short Specifications — Alta-ს მსგავსი bulk insert
    // ============================================

    private function createShortSpecifications(Product $product, array $productData): void
    {
        try {
            if (empty($productData['mainSpecification'])) {
                return;
            }

            $specs = [];
            $count = 0;

            foreach ($productData['mainSpecification'] as $spec) {
                if ($count >= self::SHORT_SPEC_LIMIT) break;

                $name  = $this->sanitizeString($spec['specificationName'] ?? null);
                $value = $this->sanitizeString($spec['specificationMeaning'] ?? null);

                if (empty($name)) continue;

                $specs[] = [
                    'product_id' => $product->id,
                    'locale'     => 'ka',
                    'name'       => $name,
                    'value'      => $value ?? '',
                    'sort_order' => $count,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $count++;
            }

            if (!empty($specs)) {
                ProductShortSpecification::insert($specs);
            }

        } catch (Exception $e) {
            Log::error("❌ Error creating short specs for Elite product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Images — Alta-ს მსგავსი (main + gallery)
    // ============================================

    private function downloadAndSaveImages(Product $product, array $productData): void
    {
        try {
            $images = [];
            if (!empty($productData['imageUrl'])) {
                $images[] = $productData['imageUrl'];
            }
            if (!empty($productData['images']) && is_array($productData['images'])) {
                $images = array_merge($images, $productData['images']);
            }
            $images = array_values(array_unique(array_filter($images)));

            if (empty($images)) {
                return;
            }

            $processedUrls = [];
            $mainImageSet  = false;
            $galleryImages = [];

            foreach ($images as $index => $imageUrl) {
                if (in_array($imageUrl, $processedUrls)) {
                    continue;
                }
                $processedUrls[] = $imageUrl;

                try {
                    $response = Http::timeout(30)
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
                            'Referer'    => 'https://ee.ge/',
                        ])
                        ->get($imageUrl);

                    if (!$response->successful()) {
                        Log::warning("⚠️ Elite: სურათი ვერ ჩამოიტვირთა: {$imageUrl}");
                        continue;
                    }

                    $imageSize = strlen($response->body());
                    if ($imageSize > self::MAX_IMAGE_SIZE) {
                        Log::warning("⚠️ Elite: სურათი ძალიან დიდია ({$imageSize} bytes): {$imageUrl}");
                        continue;
                    }

                    $ext      = $this->getImageExtension($imageUrl);
                    $filename = Str::random(40) . '.' . $ext;
                    $path     = "uploads/products/{$product->id}/{$filename}";

                    Storage::disk('public')->put($path, $response->body());

                    if ($index === 0 && !$mainImageSet) {
                        $product->update(['main_image' => $path]);
                        $mainImageSet = true;
                    } else {
                        $galleryImages[] = [
                            'product_id' => $product->id,
                            'path'       => $path,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                } catch (Exception $e) {
                    Log::warning("⚠️ Elite: სურათის შეცდომა: {$e->getMessage()}");
                    continue;
                }
            }

            if (!empty($galleryImages)) {
                ProductImage::insert($galleryImages);
                Log::info("📦 Elite: Inserted " . count($galleryImages) . " gallery images for product {$product->id}");
            }

            if (!$mainImageSet) {
                $product->update(['main_image' => null]);
            }

        } catch (Exception $e) {
            Log::error("❌ Error downloading images for Elite product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Helpers
    // ============================================

    private function sanitizeString(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $trimmed = trim($value);
        return !empty($trimmed) ? $trimmed : null;
    }

    protected function getImageExtension(string $url): string
    {
        try {
            $parsed = parse_url($url);
            $path   = $parsed['path'] ?? '';
            $ext    = pathinfo($path, PATHINFO_EXTENSION);

            $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            return in_array(strtolower($ext), $validExtensions) ? strtolower($ext) : 'jpg';

        } catch (Exception $e) {
            Log::warning("⚠️ Error getting image extension: {$e->getMessage()}");
            return 'jpg';
        }
    }
}