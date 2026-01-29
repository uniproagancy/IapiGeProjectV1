<?php

namespace App\Jobs;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
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
use App\Services\Translation\GoogleTranslation;
use Exception;

class ZoommerProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $productData;
    protected array $productAvailability;

    // ✅ Improved retry settings
    public int $tries = 3;
    public int $timeout = 300;  // ✅ Increased to 5 minutes
    public int $maxExceptions = 3;
    public int $backoffMultiplier = 2;

    public function __construct(array $productData, array $productAvailability = [])
    {
        $this->productData = $productData;
        $this->productAvailability = $productAvailability;
    }

    /**
     * ✅ Execute the job
     */
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
                'error' => $e,
            ]);

            // ✅ Retry with exponential backoff
            if ($this->attempts() < $this->tries) {
                $this->release($this->getRetryDelay());
            } else {
                Log::critical("🚫 Job failed after {$this->tries} attempts: {$this->productData['id']}");
            }
        }
    }

    /**
     * ✅ Get exponential backoff delay
     */
    private function getRetryDelay(): int
    {
        return pow($this->backoffMultiplier, $this->attempts()) * 60;  // 2m, 4m, 8m
    }

    /**
     * ✅ Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error("🚨 Job permanently failed for product {$this->productData['id']}", [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * ✅ Main logic - check stock and save
     */
    public function saveProductWithVariants(array $productData, array $productAvailability): void
    {
        // ✅ Validate required data
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
                Log::info("⏭️  Skipping product (no Tbilisi stock): {$productData['id']}");
            }
        }
    }

    /**
     * ✅ Check if product has stock in Tbilisi
     */
    private function checkTbilisiStock(array $availability): bool
    {
        if (empty($availability)) {
            return false;
        }

        return collect($availability)
            ->where('city', 'Tbilisi')
            ->contains(fn($store) => $store['inStock'] === true);
    }

    /**
     * ✅ Update existing product price and stock
     */
    private function updateExistingProduct(array $productData, bool $hasStock): void
    {
        try {
            $product = Product::where('supplier_product_id', $productData['id'])->firstOrFail();

            $productPrice = $productData['previousPrice'] ?? $productData['price'] ?? 0;
            $discountPrice = $productData['previousPrice'] ? $productData['price'] : null;

            if($productPrice > 1000) {
                $productPrice = $productPrice + ($productPrice / 100 * 5);
            } else {
                $productPrice = $productPrice + ($productPrice / 100 * 10);
            }

            if($discountPrice > 1000) {
                $discountPrice = $discountPrice + ($discountPrice / 100 * 5);
            } else {
                $discountPrice = $discountPrice + ($discountPrice / 100 * 10);
            }

            // ✅ Update price
            $product->price()->update([
                'dealer_price' => $productPrice,
                'regular_price' => $productPrice,
                'discount_price' => $discountPrice,
                'discount_percent' => $productData['discountPercent'] ?? 0,
            ]);

            // ✅ Update stock
            $product->update([
                'quantity' => $hasStock ? 5 : 0,
                'in_stock' => $hasStock ? 1 : 0,
                'show' => $hasStock ? 1 : 0,
            ]);

            Log::info("✏️  Updated product: {$product->id}");

        } catch (Exception $e) {
            Log::error("Error updating product {$productData['id']}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * ✅ Create new product with all relationships
     */
    private function createNewProduct(array $productData, bool $hasStock): void
    {
        DB::transaction(function () use ($productData, $hasStock) {
            try {
                // ✅ Get or create brand
                $brand_id = $this->getBrandId($productData);

                // ✅ Create product
                $product = Product::create([
                    'supplier_product_id' => $productData['id'],
                    'brand_id' => $brand_id,
                    'category_id' => 4,
                    'sku' => $productData['barCode'] ?? null,
                    'supplier_id' => 4,
                    'main_image' => 1,
                    'active' => 1,
                    'quantity' => $hasStock ? 5 : 0,
                    'in_stock' => $hasStock ? 1 : 0,
                    'show' => $hasStock ? 1 : 0,
                ]);

                // ✅ Create related data
                $this->createPrice($product, $productData);
                $this->createTranslations($product, $productData);
                $this->createFullSpecifications($product, $productData);
                $this->createVariations($product, $productData);
                $this->downloadImages($product, $productData);
                $this->createShortSpecifications($product, $productData);

                Log::info("✨ Created new product: {$product->id}");

            } catch (Exception $e) {
                Log::error("Error creating product {$productData['id']}: {$e->getMessage()}");
                throw $e;
            }
        });
    }

    /**
     * ✅ Get brand ID - improved with error handling
     */
    private function getBrandId(array $productData): int
    {
        try {
            // Find brand from specifications
            $specGroup = collect($productData['specificationGroup'] ?? [])
                ->firstWhere('groupName', 'Brand');

            if (!empty($specGroup) && !empty($specGroup['specifications'][0]['specificationMeaning'])) {
                $brandName = $specGroup['specifications'][0]['specificationMeaning'];

                $brand = ProductBrand::whereHas('translations', function ($query) use ($brandName) {
                    $query->where('title', 'like', $brandName);
                })->first();

                if ($brand) {
                    return $brand->id;
                }
            }

            // ✅ Default brand if not found
            return 6;

        } catch (Exception $e) {
            Log::warning("Error finding brand: {$e->getMessage()}");
            return 6;  // Default brand
        }
    }

    /**
     * ✅ Create product price
     */
    private function createPrice(Product $product, array $productData): void
    {
        try {
            $productPrice = $productData['previousPrice'] ?? $productData['price'] ?? 0;
            $discountPrice = $productData['previousPrice'] ? $productData['price'] : null;

            // ✅ Calculate markup
            $markup = $productPrice > 1000 ? 0.05 : 0.10;
            $finalPrice = $productPrice * (1 + $markup);

            ProductPrice::create([
                'product_id' => $product->id,
                'dealer_price' => $finalPrice,
                'regular_price' => $finalPrice,
                'discount_price' => $discountPrice,
                'discount_percent' => $productData['discountPercent'] ?? 0,
            ]);

        } catch (Exception $e) {
            Log::error("Error creating price for product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * ✅ Create product translations
     */
    private function createTranslations(Product $product, array $productData): void
    {
        try {
            $locales = ['ka', 'en', 'ru'];
            $baseSlug = Str::slug($productData['name'] ?? 'product', '-');
            $slugWithId = "{$baseSlug}-{$product->id}";

            foreach ($locales as $locale) {
                ProductTranslation::create([
                    'product_id' => $product->id,
                    'locale' => $locale,
                    'title' => $productData['name'] ?? 'Unnamed Product',
                    'slug' => $slugWithId,
                    'description' => $locale === 'ka' ? ($productData['description'] ?? null) : null,
                    'keywords' => null,
                ]);
            }

        } catch (Exception $e) {
            Log::error("Error creating translations for product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * ✅ Create product variations
     */
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
                    'name' => $specification['specificationName'],
                    'value' => $specification['specificationMeaning'] ?? null,
                ]);

                // ✅ Create variation items
                if (!empty($specification['specificationMeaningsList'])) {
                    foreach ($specification['specificationMeaningsList'] as $item) {
                        ProductVariationItem::create([
                            'variation_id' => $variation->id,
                            'is_color' => isset($item['isColor']) && $item['isColor'] ? 1 : 0,
                            'supplier_product_id' => $item['productId'] ?? null,
                            'value' => $item['value'] ?? null,
                        ]);
                    }
                }
            }

        } catch (Exception $e) {
            Log::error("Error creating variations for product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * ✅ Create full specifications
     */
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
                    'name' => $specificationGroup['groupName'],
                ]);

                // ✅ Create specification items
                if (!empty($specificationGroup['specifications'])) {
                    foreach ($specificationGroup['specifications'] as $spec) {
                        if (empty($spec['specificationName'])) {
                            continue;
                        }

                        $isFilter = !empty($spec['specificationLinkedUrl']);

                        ProductFullSpecificationItem::create([
                            'section_id' => $section->id,
                            'name' => $spec['specificationName'],
                            'value' => $spec['specificationMeaning'] ?? null,
                            'filter' => $isFilter ? 1 : 0,
                        ]);
                    }
                }
            }

        } catch (Exception $e) {
            Log::error("Error creating specifications for product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * ✅ Download and save product images
     */
    private function downloadImages(Product $product, array $productData): void
    {
        try {
            if (empty($productData['images'])) {
                return;
            }

            foreach ($productData['images'] as $index => $imageUrl) {
                try {
                    // ✅ Skip invalid URLs
                    if (empty($imageUrl)) {
                        continue;
                    }

                    // ✅ Download image with timeout
                    $response = Http::timeout(30)->get($imageUrl);

                    if (!$response->successful()) {
                        Log::warning("Failed to download image for product {$product->id}: {$imageUrl}");
                        continue;
                    }

                    // ✅ Get extension
                    $ext = $this->getImageExtension($imageUrl);
                    $filename = Str::random(40) . '.' . $ext;
                    $path = "uploads/products/{$product->id}/{$filename}";

                    // ✅ Save image
                    Storage::disk('public')->put($path, $response->body());

                    // ✅ Set main image or create product image
                    if ($index === 0) {
                        $product->update(['main_image' => $path]);
                    } else {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'path' => $path,
                        ]);
                    }

                    Log::info("✅ Downloaded image for product {$product->id}: {$filename}");

                } catch (Exception $e) {
                    Log::warning("Error downloading image {$imageUrl}: {$e->getMessage()}");
                    continue;  // ✅ Continue with next image instead of returning
                }
            }

        } catch (Exception $e) {
            Log::error("Error downloading images for product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * ✅ Create short specifications
     */
    private function createShortSpecifications(Product $product, array $productData): void
    {
        try {
            if (empty($productData['mainSpecification'])) {
                return;
            }

            $translator = new GoogleTranslation();

            foreach ($productData['mainSpecification'] as $spec) {
                if (empty($spec['specificationName'])) {
                    continue;
                }

                ProductShortSpecification::create([
                    'product_id' => $product->id,
                    'name' => $translator->translateToGeorgian($spec['specificationName']),
                    'value' => $translator->translateToGeorgian($spec['specificationMeaning'] ?? ''),
                ]);
            }

        } catch (Exception $e) {
            Log::error("Error creating short specifications for product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * ✅ Get image extension from URL
     */
    protected function getImageExtension(string $url): string
    {
        try {
            $parsed = parse_url($url);
            $path = $parsed['path'] ?? '';
            $ext = pathinfo($path, PATHINFO_EXTENSION);

            // ✅ Validate extension
            $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            return in_array(strtolower($ext), $validExtensions) ? strtolower($ext) : 'jpg';

        } catch (Exception $e) {
            Log::warning("Error getting image extension from {$url}: {$e->getMessage()}");
            return 'jpg';
        }
    }
}