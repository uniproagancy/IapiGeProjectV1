<?php

namespace App\Jobs;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductFullSpecificationItem;
use App\Models\Product\ProductFullSpecificationSection;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductShortSpecification;
use App\Models\Product\ProductTranslation;
use App\Models\Product\ProductVariation;
use App\Models\Product\ProductVariationItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class ZoommerProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $productData;
    protected array $productAvailability;

    public int $tries             = 3;
    public int $timeout           = 300;
    public int $maxExceptions     = 3;
    public int $backoffMultiplier = 2;

    public function __construct(array $productData, array $productAvailability = [])
    {
        $this->productData         = $productData;
        $this->productAvailability = $productAvailability;
    }

    public function handle(): void
    {
        try {
            Log::info("🔄 Processing Zoommer product: {$this->productData['id']}", [
                'attempt' => $this->attempts(),
            ]);

            $this->saveProductWithVariants($this->productData, $this->productAvailability);

            Log::info("✅ Product saved: {$this->productData['id']}");

        } catch (Exception $e) {
            Log::error("❌ Error processing product {$this->productData['id']}: {$e->getMessage()}", [
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->getRetryDelay());
            } else {
                Log::critical("🚫 Job failed after {$this->tries} attempts: {$this->productData['id']}");
            }
        }
    }

    private function getRetryDelay(): int
    {
        return pow($this->backoffMultiplier, $this->attempts()) * 60;
    }

    public function failed(Exception $exception): void
    {
        Log::error("🚨 Job permanently failed for product {$this->productData['id']}", [
            'error'    => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    private function calculatePrice(float $price): float
    {
        if ($price < 100) {
            return $price + 30;
        } elseif ($price <= 500) {
            return $price + 50;
        } else {
            return $price + 100;
        }
    }

    // ============================================
    // Category
    // ============================================

    private function getCategoryId(array $productData): int
    {
        $categoryName       = $productData['categoryName'] ?? null;

        if ($categoryName) {
            $category = ProductCategory::where('zoommer_category_name', $categoryName)->first();
            if ($category) {
                return $category->id;
            }
        }

        Log::info("⚠️ Zoommer category not mapped: categoryName={$categoryName}");
        return 4;
    }

    // ============================================
    // Brand
    // ============================================

    private function getBrandId(array $productData): int
    {
        try {
            $brandName = null;

            foreach ($productData['specificationGroup'] ?? [] as $group) {
                foreach ($group['specifications'] ?? [] as $spec) {
                    if ($spec['specificationName'] === 'ბრენდი') {
                        $brandName = $spec['specificationMeaning'] ?? null;
                        break 2;
                    }
                }
            }

            if (empty($brandName)) {
                $brandName = $productData['brandName'] ?? null;
            }

            if (empty($brandName)) {
                return 6;
            }

            $brand = ProductBrand::whereHas('translations',
                fn ($q) => $q->where('title', 'like', $brandName)
            )->first();

            return $brand->id ?? 6;

        } catch (Exception $e) {
            Log::warning("Error finding brand: {$e->getMessage()}");
            return 6;
        }
    }

    // ============================================
    // Main Logic
    // ============================================

    public function saveProductWithVariants(array $productData, array $productAvailability): void
    {
        if (empty($productData['id'])) {
            throw new Exception('Product ID is required');
        }

        $hasStock = $this->checkTbilisiStock($productAvailability);

        if (Product::where('supplier_product_id', $productData['id'])->exists()) {
            $this->updateExistingProduct($productData, $hasStock);
        } else {
            if ($hasStock) {
                $this->createNewProduct($productData, $hasStock);
            } else {
                Log::info("⏭️  Skipping new product (no Tbilisi stock): {$productData['id']}");
            }
        }
    }

    private function checkTbilisiStock(array $availability): bool
    {
        if (empty($availability)) {
            return false;
        }

        return collect($availability)
            ->where('city', 'თბილისი')
            ->contains(fn($store) => $store['inStock'] === true);
    }

    // ============================================
    // Update
    // ============================================

    private function updateExistingProduct(array $productData, bool $hasStock): void
    {
        try {
            $product = Product::where('supplier_product_id', $productData['id'])->firstOrFail();

            $productPrice  = (float) ($productData['previousPrice'] ?? $productData['price'] ?? 0);
            $discountPrice = $productData['previousPrice'] ? (float) $productData['price'] : null;

            $productPrice  = $this->calculatePrice($productPrice);
            $discountPrice = $discountPrice ? $this->calculatePrice($discountPrice) : null;

            ProductPrice::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'dealer_price'     => $productPrice,
                    'regular_price'    => $productPrice,
                    'discount_price'   => $discountPrice,
                    'discount_percent' => $productData['discountPercent'] ?? 0,
                ]
            );

            $categoryId = $this->getCategoryId($productData);

            $product->update([
                'category_id' => $categoryId,
                'quantity'    => $hasStock ? 5 : 0,
                'in_stock'    => $hasStock ? 1 : 0,
                'show'        => $hasStock ? 1 : 0,
                'active'      => $hasStock ? 1 : 0,
            ]);

            ProductShortSpecification::where('product_id', $product->id)->forceDelete();
            $this->createShortSpecifications($product, $productData);

            $sectionIds = ProductFullSpecificationSection::where('product_id', $product->id)->pluck('id');
            ProductFullSpecificationItem::whereIn('section_id', $sectionIds)->forceDelete();
            ProductFullSpecificationSection::where('product_id', $product->id)->forceDelete();
            $this->createFullSpecifications($product, $productData);

            if (!empty($productData['description'])) {
                ProductTranslation::where('product_id', $product->id)
                    ->where('locale', 'ka')
                    ->update(['description' => $productData['description']]);
            }

            Log::info("🔁 Updated product: {$product->id}, category: {$categoryId}, stock: " . ($hasStock ? 'yes' : 'no'));

        } catch (Exception $e) {
            Log::error("Error updating product {$productData['id']}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Create
    // ============================================

    private function createNewProduct(array $productData, bool $hasStock): void
    {
        DB::transaction(function () use ($productData, $hasStock) {
            try {
                $brandId    = $this->getBrandId($productData);
                $categoryId = $this->getCategoryId($productData);

                $product = Product::create([
                    'supplier_product_id' => $productData['id'],
                    'brand_id'            => $brandId,
                    'category_id'         => $categoryId,
                    'sku'                 => $productData['barCode'] ?? null,
                    'supplier_id'         => 4,
                    'main_image'          => null,
                    'active'              => 1,
                    'quantity'            => $hasStock ? 5 : 0,
                    'in_stock'            => $hasStock ? 1 : 0,
                    'show'                => $hasStock ? 1 : 0,
                ]);

                $this->createPrice($product, $productData);
                $this->createTranslations($product, $productData);
                $this->createFullSpecifications($product, $productData);
                $this->createVariations($product, $productData);
                $this->downloadImages($product, $productData);
                $this->createShortSpecifications($product, $productData);

                Log::info("✨ Created new product: {$product->id}, category: {$categoryId}");

            } catch (Exception $e) {
                Log::error("Error creating product {$productData['id']}: {$e->getMessage()}");
                throw $e;
            }
        });
    }

    // ============================================
    // Price
    // ============================================

    private function createPrice(Product $product, array $productData): void
    {
        try {
            $productPrice  = (float) ($productData['previousPrice'] ?? $productData['price'] ?? 0);
            $discountPrice = $productData['previousPrice'] ? (float) $productData['price'] : null;

            $productPrice  = $this->calculatePrice($productPrice);
            $discountPrice = $discountPrice ? $this->calculatePrice($discountPrice) : null;

            ProductPrice::create([
                'product_id'       => $product->id,
                'dealer_price'     => $productPrice,
                'regular_price'    => $productPrice,
                'discount_price'   => $discountPrice,
                'discount_percent' => $productData['discountPercent'] ?? 0,
            ]);

        } catch (Exception $e) {
            Log::error("Error creating price for product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Translations
    // ============================================

    private function createTranslations(Product $product, array $productData): void
    {
        try {
            $locales    = ['ka', 'en', 'ru'];
            $name       = $productData['name'] ?? 'Unnamed Product';
            $baseSlug   = Str::slug($name, '-') . '-' . $product->id;

            foreach ($locales as $locale) {
                ProductTranslation::create([
                    'product_id'  => $product->id,
                    'locale'      => $locale,
                    'title'       => $name,
                    'slug'        => $baseSlug,
                    'description' => $locale === 'ka' ? ($productData['description'] ?? null) : null,
                    'keywords'    => null,
                ]);
            }

        } catch (Exception $e) {
            Log::error("Error creating translations for product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Variations
    // ============================================

    private function createVariations(Product $product, array $productData): void
    {
        try {
            if (empty($productData['keySpecification'])) {
                return;
            }

            foreach ($productData['keySpecification'] as $specification) {
                if (empty($specification['specificationName'])) {
                    continue;
                }

                $variation = ProductVariation::create([
                    'product_id' => $product->id,
                    'name'       => $specification['specificationName'],
                    'value'      => $specification['specificationMeaning'] ?? null,
                ]);

                if (!empty($specification['specificationMeaningsList'])) {
                    foreach ($specification['specificationMeaningsList'] as $item) {
                        ProductVariationItem::create([
                            'variation_id'        => $variation->id,
                            'is_color'            => isset($item['isColor']) && $item['isColor'] ? 1 : 0,
                            'supplier_product_id' => $item['productId'] ?? null,
                            'value'               => $item['value'] ?? null,
                        ]);
                    }
                }
            }

        } catch (Exception $e) {
            Log::error("Error creating variations for product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Full Specifications
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
                            'name'       => $spec['specificationName'],
                            'value'      => $spec['specificationMeaning'] ?? null,
                            'filter'     => !empty($spec['specificationLinkedUrl']) ? 1 : 0,
                        ]);
                    }
                }
            }

        } catch (Exception $e) {
            Log::error("Error creating specifications for product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Images
    // ============================================

    private function downloadImages(Product $product, array $productData): void
    {
        try {
            if (empty($productData['images'])) {
                return;
            }

            foreach ($productData['images'] as $index => $imageUrl) {
                try {
                    if (empty($imageUrl)) continue;

                    $response = Http::timeout(30)
                        ->withHeaders([
                            'Accept'          => 'application/json, text/plain, */*',
                            'Accept-Language' => 'ka',
                            'Referer'         => 'https://zoommer.ge/',
                            'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',
                            'os'              => 'web',
                            'Cookie'          => 'zoommer-access_token=' . env('ZOOMMER_ACCESS_TOKEN') . '; zoommer-cookie_agreed=true; cf_clearance=' . env('ZOOMMER_CF_CLEARANCE'),
                        ])
                        ->get($imageUrl);

                    if (!$response->successful()) {
                        Log::warning("Failed to download image for product {$product->id}: {$imageUrl}");
                        continue;
                    }

                    $ext      = $this->getImageExtension($imageUrl);
                    $filename = Str::random(40) . '.' . $ext;
                    $path     = "uploads/products/{$product->id}/{$filename}";

                    Storage::disk('public')->put($path, $response->body());

                    if ($index === 0) {
                        $product->update(['main_image' => $path]);
                    } else {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'path'       => $path,
                        ]);
                    }

                    Log::info("✅ Downloaded image for product {$product->id}: {$filename}");

                } catch (Exception $e) {
                    Log::warning("Error downloading image {$imageUrl}: {$e->getMessage()}");
                    continue;
                }
            }

        } catch (Exception $e) {
            Log::error("Error downloading images for product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Short Specifications
    // ============================================

    private function createShortSpecifications(Product $product, array $productData): void
    {
        try {
            if (empty($productData['mainSpecification'])) {
                return;
            }

            $specs = [];

            foreach ($productData['mainSpecification'] as $spec) {
                if (empty($spec['specificationName'])) continue;

                $specs[] = [
                    'product_id' => $product->id,
                    'name'       => $spec['specificationName'],
                    'value'      => $spec['specificationMeaning'] ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($specs)) {
                ProductShortSpecification::insert($specs);
            }

        } catch (Exception $e) {
            Log::error("Error creating short specifications for product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Helpers
    // ============================================

    protected function getImageExtension(string $url): string
    {
        try {
            $parsed = parse_url($url);
            $path   = $parsed['path'] ?? '';
            $ext    = pathinfo($path, PATHINFO_EXTENSION);

            $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            return in_array(strtolower($ext), $validExtensions) ? strtolower($ext) : 'jpg';

        } catch (Exception $e) {
            Log::warning("Error getting image extension from {$url}: {$e->getMessage()}");
            return 'jpg';
        }
    }
}