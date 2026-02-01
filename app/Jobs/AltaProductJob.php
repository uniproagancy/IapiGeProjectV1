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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Translation\GoogleTranslation;
use Exception;

/**
 * ✅ Improved ALTA Product Job
 *
 * Optimizations:
 * - Brand lookup caching
 * - Config-based hardcoded values
 * - Fixed image download duplication
 * - Image size validation
 * - Translation caching
 * - Bulk image inserts
 * - Better error handling
 */
class AltaProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $productData;
    protected array $productAvailability;

    // ✅ Improved retry settings
    public int $tries = 3;
    public int $timeout = 300;
    public int $maxExceptions = 3;
    public int $backoffMultiplier = 2;

    // ✅ Configuration
    private const CACHE_DURATION_BRAND = 24 * 60;  // 24 hours
    private const CACHE_DURATION_TRANSLATION = 30 * 24 * 60;  // 30 days
    private const MAX_IMAGE_SIZE = 5 * 1024 * 1024;  // 5MB

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
            Log::info("🔄 Processing ALTA product: {$this->productData['id']}", [
                'attempt' => $this->attempts(),
            ]);

            $this->saveProductWithVariants($this->productData, $this->productAvailability);

            Log::info("✅ Product saved: {$this->productData['id']}");

        } catch (Exception $e) {
            Log::error("❌ Error processing product {$this->productData['id']}: {$e->getMessage()}", [
                'attempt' => $this->attempts(),
                'trace' => $e->getTraceAsString(),
            ]);

            // ✅ Retry with exponential backoff
            if ($this->attempts() < $this->tries) {
                $this->release($this->getRetryDelay());
            } else {
                Log::critical("🚫 Job permanently failed: {$this->productData['id']}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * ✅ Get exponential backoff delay
     */
    private function getRetryDelay(): int
    {
        return pow($this->backoffMultiplier, $this->attempts()) * 60;
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
     * ✅ Main logic
     */
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
                Log::info("⏭️  Skipping product (no Tbilisi stock): {$productData['id']}");
            }
        }
    }

    /**
     * ✅ Check Tbilisi stock
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
     * ✅ Update existing product
     */
    private function updateExistingProduct(array $productData, bool $hasStock): void
    {
        try {
            $product = Product::where('supplier_product_id', $productData['id'])->firstOrFail();

            $productPrice = $productData['previousPrice'] ?? $productData['price'] ?? 0;
            $discountPrice = $productData['previousPrice'] ? $productData['price'] : null;

            $product->price()->update([
                'dealer_price' => $productPrice,
                'regular_price' => $productPrice,
                'discount_price' => $discountPrice,
                'discount_percent' => $productData['discountPercent'] ?? 0,
            ]);

            $product->update([
                'quantity' => $hasStock ? 5 : 0,
                'in_stock' => $hasStock ? 1 : 0,
                'show' => $hasStock ? 1 : 0,
            ]);

            Log::info("✏️  Updated product: {$product->id}");

        } catch (Exception $e) {
            Log::error("❌ Error updating product {$productData['id']}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * ✅ Create new product
     */
    private function createNewProduct(array $productData, bool $hasStock): void
    {
        DB::transaction(function () use ($productData, $hasStock) {
            try {
                // ✅ Get brand ID (cached)
                $brandId = $this->getBrandId($productId);

                // ✅ Create product
                $product = Product::create([
                    'supplier_product_id' => $productData['id'],
                    'brand_id' => $brandId,
                    'category_id' => Config::get('services.alta.category_id', 3),
                    'sku' => 'ALTA-' . ($productData['barCode'] ?? null),
                    'supplier_id' => Config::get('services.alta.supplier_id', 2),
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
                $this->downloadAndSaveImages($product, $productData);
                $this->createShortSpecifications($product, $productData);

                Log::info("✨ Created new product: {$product->id}");

            } catch (Exception $e) {
                Log::error("❌ Error creating product: {$e->getMessage()}");
                throw $e;
            }
        });
    }

    /**
     * ✅ Get brand ID (with caching)
     */
    private function getBrandId(array $productData): int
    {
        try {
            // Find brand name from specifications
            $specGroup = collect($productData['specificationGroup'] ?? [])
                ->firstWhere('groupName', 'Brand');

            if (empty($specGroup) || empty($specGroup['specifications'][0])) {
                return Config::get('services.alta.default_brand_id', 6);
            }

            $brandName = $specGroup['specifications'][0]['specificationMeaning'] ?? null;

            if (empty($brandName)) {
                return Config::get('services.alta.default_brand_id', 6);
            }

            // ✅ Cache brand lookup (24 hours)
            return Cache::remember(
                'alta_brand_' . md5($brandName),
                now()->addMinutes(self::CACHE_DURATION_BRAND),
                function () use ($brandName) {
                    $brand = ProductBrand::whereHas('translations',
                        fn($q) => $q->where('title', 'like', $brandName)
                    )->first();

                    return $brand->id ?? Config::get('services.alta.default_brand_id', 6);
                }
            );

        } catch (Exception $e) {
            Log::warning("⚠️  Error finding brand: {$e->getMessage()}");
            return Config::get('services.alta.default_brand_id', 6);
        }
    }

    /**
     * ✅ Create price
     */
    private function createPrice(Product $product, array $productData): void
    {
        try {
            $productPrice = $productData['previousPrice'] ?? $productData['price'] ?? 0;
            $discountPrice = $productData['previousPrice'] ? $productData['price'] : null;

            ProductPrice::create([
                'product_id' => $product->id,
                'dealer_price' => $productPrice,
                'regular_price' => $productPrice,
                'discount_price' => $discountPrice,
                'discount_percent' => $productData['discountPercent'] ?? 0,
            ]);

        } catch (Exception $e) {
            Log::error("❌ Error creating price: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * ✅ Create translations
     */
    private function createTranslations(Product $product, array $productData): void
    {
        try {
            $locales = ['ka', 'en', 'ru'];
            $productName = $this->sanitizeString($productData['name']) ?: 'Unnamed Product';

            foreach ($locales as $locale) {
                ProductTranslation::create([
                    'product_id' => $product->id,
                    'locale' => $locale,
                    'title' => $productName,
                    'slug' => Str::slug($productName) . "-{$product->id}",
                    'description' => $locale === 'ka' ? $this->sanitizeString($productData['description']) : null,
                    'keywords' => null,
                ]);
            }

        } catch (Exception $e) {
            Log::error("❌ Error creating translations: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * ✅ Create variations
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
            Log::error("❌ Error creating variations: {$e->getMessage()}");
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

                if (!empty($specificationGroup['specifications'])) {
                    foreach ($specificationGroup['specifications'] as $spec) {
                        if (empty($spec['specificationName'])) {
                            continue;
                        }

                        ProductFullSpecificationItem::create([
                            'section_id' => $section->id,
                            'name' => $spec['specificationName'],
                            'value' => $this->sanitizeString($spec['specificationMeaning']),
                            'filter' => !empty($spec['specificationLinkedUrl']) ? 1 : 0,
                        ]);
                    }
                }
            }

        } catch (Exception $e) {
            Log::error("❌ Error creating specifications: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * ✅ Download and save images (FIXED - no duplication)
     */
    private function downloadAndSaveImages(Product $product, array $productData): void
    {
        try {
            if (empty($productData['images'])) {
                return;
            }

            $images = [];
            $processedUrls = [];

            foreach ($productData['images'] as $index => $imageUrl) {
                // ✅ Skip duplicates
                if (in_array($imageUrl, $processedUrls)) {
                    Log::debug("⏭️  Skipping duplicate image: {$imageUrl}");
                    continue;
                }

                $processedUrls[] = $imageUrl;

                try {
                    // ✅ Download image (FIXED - single HTTP call)
                    $imageUrll = "https://api.scrape.do/?url=" . urlencode($imageUrl) .
                    "&token={$this->scrape_token}";
                    $response = Http::timeout(30)
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                        ->get($imageUrll);

                    if (!$response->successful()) {
                        Log::warning("⚠️  Failed to download image: {$imageUrll}");
                        continue;
                    }

                    // ✅ Check image size
                    $imageSize = strlen($response->body());
                    if ($imageSize > self::MAX_IMAGE_SIZE) {
                        Log::warning("⚠️  Image too large ({$imageSize} bytes): {$imageUrll}");
                        continue;
                    }

                    // ✅ Save image
                    $ext = $this->getImageExtension($imageUrll);
                    $filename = Str::random(40) . '.' . $ext;
                    $path = "uploads/products/{$product->id}/{$filename}";

                    Storage::disk('public')->put($path, $response->body());

                    // ✅ Set main image or collect for bulk insert
                    if ($index === 0) {
                        $product->update(['main_image' => $path]);
                    } else {
                        $images[] = [
                            'product_id' => $product->id,
                            'path' => $path,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    Log::info("✅ Downloaded image: {$filename}");

                } catch (Exception $e) {
                    Log::warning("⚠️  Error downloading {$imageUrll}: {$e->getMessage()}");
                    continue;
                }
            }

            // ✅ Bulk insert images (faster than loop)
            if (!empty($images)) {
                ProductImage::insert($images);
                Log::info("📦 Bulk inserted " . count($images) . " images");
            }

        } catch (Exception $e) {
            Log::error("❌ Error downloading images: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * ✅ Create short specifications (with translation caching)
     */
    private function createShortSpecifications(Product $product, array $productData): void
    {
        try {
            if (empty($productData['mainSpecification'])) {
                return;
            }

            $translator = new GoogleTranslation();
            $specs = [];

            foreach ($productData['mainSpecification'] as $spec) {
                if (empty($spec['specificationName'])) {
                    continue;
                }

                $name = $this->sanitizeString($spec['specificationName']);
                $value = $this->sanitizeString($spec['specificationMeaning']);

                if (empty($name)) {
                    continue;
                }

                // ✅ Cache translations (30 days)
                $translatedName = Cache::remember(
                    'alta_translation_' . md5($name),
                    now()->addMinutes(self::CACHE_DURATION_TRANSLATION),
                    fn() => $translator->translateToGeorgian($name)
                );

                $translatedValue = empty($value) ? '' : Cache::remember(
                    'alta_translation_' . md5($value),
                    now()->addMinutes(self::CACHE_DURATION_TRANSLATION),
                    fn() => $translator->translateToGeorgian($value)
                );

                $specs[] = [
                    'product_id' => $product->id,
                    'name' => $translatedName,
                    'value' => $translatedValue,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // ✅ Bulk insert specs
            if (!empty($specs)) {
                ProductShortSpecification::insert($specs);
            }

        } catch (Exception $e) {
            Log::error("❌ Error creating short specifications: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * ✅ Sanitize string input
     */
    private function sanitizeString(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $trimmed = trim($value);
        return !empty($trimmed) ? $trimmed : null;
    }

    /**
     * ✅ Get image extension
     */
    protected function getImageExtension(string $url): string
    {
        try {
            $parsed = parse_url($url);
            $path = $parsed['path'] ?? '';
            $ext = pathinfo($path, PATHINFO_EXTENSION);

            $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            return in_array(strtolower($ext), $validExtensions) ? strtolower($ext) : 'jpg';

        } catch (Exception $e) {
            Log::warning("⚠️  Error getting image extension: {$e->getMessage()}");
            return 'jpg';
        }
    }
}