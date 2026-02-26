<?php

namespace App\Jobs;

use App\Models\AltaID;
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
use SoapClient;

class AltaProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $productData = [];
    protected array $productAvailability = [];

    public int $tries = 3;
    public int $timeout = 300;
    public int $maxExceptions = 3;
    public int $backoffMultiplier = 2;

    private const CACHE_DURATION_BRAND = 24 * 60;
    private const CACHE_DURATION_TRANSLATION = 30 * 24 * 60;
    private const MAX_IMAGE_SIZE = 5 * 1024 * 1024;

    public function __construct(array $productData = [], array $productAvailability = [])
    {
        $this->productData = $productData;
        $this->productAvailability = $productAvailability;
    }

    // ============================================
    // Handle
    // ============================================

    public function handle(): void
    {
        try {
            if (empty($this->productData) || empty($this->productData['id'])) {
                Log::warning('⚠️  AltaProductJob: productData is empty or missing id');
                return;
            }

            Log::info("🔄 Processing ALTA product: {$this->productData['id']}", [
                'attempt' => $this->attempts(),
            ]);

            $this->saveProductWithVariants($this->productData, $this->productAvailability);

            Log::info("✅ Product saved: {$this->productData['id']}");

        } catch (Exception $e) {
            $productId = $this->productData['id'] ?? 'unknown';
            Log::error("❌ Error processing product {$productId}: {$e->getMessage()}", [
                'attempt' => $this->attempts(),
                'trace'   => $e->getTraceAsString(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->getRetryDelay());
            } else {
                Log::critical("🚫 Job permanently failed: {$productId}", [
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
        $productId = $this->productData['id'] ?? 'unknown';
        Log::error("🚨 Job permanently failed for product {$productId}", [
            'error'    => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    // ============================================
    // Main Logic
    // ============================================

    public function saveProductWithVariants(array $productData, array $productAvailability): void
    {
        if (empty($productData['id'])) {
            throw new Exception('Product ID is required');
        }
        $b2bStock = AltaID::where('product_id', $productData['barCode'])->first();
        if (Product::where('sku', $productData['barCode'])->exists()) {
            $this->updateExistingProduct($productData, $b2bStock);
        } else {
            if ($b2bStock['quantity'] >= 2) {
                $this->createNewProduct($productData, $b2bStock);
            } else {
                Log::info("⏭️  Skipping product (no B2B stock): {$productData['id']}");
            }
        }
    }

    // ============================================
    // Update Existing Product
    // ============================================

    private function updateExistingProduct(array $productData, array $b2bStock): void
    {
        try {
            $product = Product::where('sku', 'ALTA-' . $productData['barCode'])->firstOrFail();

            DB::transaction(function () use ($product, $productData, $b2bStock) {
                $productPrice  = $productData['previousPrice'] ?? $productData['price'] ?? 0;
                $discountPrice = $productData['previousPrice'] ? $productData['price'] : null;

                $product->price()->update([
                    'dealer_price'     => $productPrice,
                    'regular_price'    => $productPrice,
                    'discount_price'   => $discountPrice,
                    'discount_percent' => $productData['discountPercent'] ?? 0,
                ]);

                $show     = $b2bStock['quantity'] >= 1 ? 1 : 0;
                $quantity = $b2bStock['quantity'] >= 1 ? $b2bStock['quantity'] : 0;
                $in_stock = $b2bStock['quantity'] >= 1 ? 1 : 0;

                $product->update([
                    'quantity' => $quantity,
                    'in_stock' => $in_stock,
                    'show'     => $show,
                ]);

                if (!empty($productData['images'])) {
                    $this->updateProductImages($product, $productData);
                }

                Log::info("✏️  Updated product: {$product->id}", [
                    'quantity'       => $quantity,
                    'in_stock'       => $in_stock,
                    'show'           => $show,
                    'qty_text'       => $b2bStock['qty_text'] ?? null,
                    'price'          => $productPrice,
                    'images_updated' => !empty($productData['images']),
                ]);
            });

        } catch (Exception $e) {
            Log::error("❌ Error updating product {$productData['id']}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Update Product Images
    // ============================================

    private function updateProductImages(Product $product, array $productData): void
    {
        try {
            if (empty($productData['images'])) {
                return;
            }

            $images        = [];
            $processedUrls = [];
            $mainImageSet  = false;

            foreach ($productData['images'] as $index => $imageUrl) {
                if (in_array($imageUrl, $processedUrls)) {
                    Log::debug("⏭️  Skipping duplicate image: {$imageUrl}");
                    continue;
                }

                $processedUrls[] = $imageUrl;

                try {
                    $urlToFetch = $this->getScrapeUrl($imageUrl);
                    $response   = Http::timeout(30)
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                        ->get($urlToFetch);

                    if (!$response->successful()) {
                        Log::warning("⚠️  Failed to download image: {$urlToFetch}");
                        continue;
                    }

                    $imageSize = strlen($response->body());
                    if ($imageSize > self::MAX_IMAGE_SIZE) {
                        Log::warning("⚠️  Image too large ({$imageSize} bytes): {$urlToFetch}");
                        continue;
                    }

                    $ext      = $this->getImageExtension($imageUrl);
                    $filename = Str::random(40) . '.' . $ext;
                    $path     = "uploads/products/{$product->id}/{$filename}";

                    Storage::disk('public')->put($path, $response->body());

                    if ($index === 0 && !$mainImageSet) {
                        $product->update(['main_image' => $path]);
                        $mainImageSet = true;
                        Log::info("✅ Set main image: {$filename}");
                    } else {
                        $images[] = [
                            'product_id' => $product->id,
                            'path'       => $path,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                } catch (Exception $e) {
                    Log::warning("⚠️  Error downloading image: {$e->getMessage()}");
                    continue;
                }
            }

            // ✅ გასწორდა: ძველები ყოველთვის წაიშლება
            $oldImages = ProductImage::where('product_id', $product->id)->withTrashed()->get();
            foreach ($oldImages as $oldImage) {
                try {
                    if (!empty($oldImage->path) && Storage::disk('public')->exists($oldImage->path)) {
                        Storage::disk('public')->delete($oldImage->path);
                        Log::info("🗑️  Deleted image file: {$oldImage->path}");
                    }
                } catch (Exception $e) {
                    Log::warning("⚠️  Error deleting: {$e->getMessage()}");
                }
            }
            ProductImage::where('product_id', $product->id)->forceDelete();

            if (!empty($images)) {
                ProductImage::insert($images);
                Log::info("📦 Bulk inserted " . count($images) . " images");
            }

            // ✅ გასწორდა: main image ვერ დაყენდა — null-ზე დავაყენოთ
            if (!$mainImageSet) {
                Log::warning("⚠️  Main image not set for product {$product->id}");
                $product->update(['main_image' => null]);
            }

        } catch (Exception $e) {
            Log::error("❌ Error updating images: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Create New Product
    // ============================================

    private function createNewProduct(array $productData, $b2bStock): void
    {
        DB::transaction(function () use ($productData, $b2bStock) {
            try {
                $brandId = $this->getBrandId($productData);

                // ✅ გასწორდა: მნიშვნელობები პირდაპირ create-ში, ზედმეტი update აღარ არის
                $show     = $b2bStock['quantity'] >= 1 ? 1 : 0;
                $quantity = $b2bStock['quantity'] >= 1 ? $b2bStock['quantity'] : 0;
                $in_stock = $b2bStock['quantity'] >= 1 ? 1 : 0;

                $product = Product::create([
                    'supplier_product_id' => $productData['id'],
                    'brand_id'            => $brandId,
                    'category_id'         => Config::get('services.alta.category_id', 3),
                    'sku'                 => 'ALTA-' . ($productData['barCode'] ?? null),
                    'supplier_id'         => Config::get('services.alta.supplier_id', 2),
                    'main_image'          => null,
                    'active'              => 1,
                    'quantity'            => $quantity,
                    'in_stock'            => $in_stock,
                    'show'                => $show,
                ]);

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

    // ============================================
    // Brand
    // ============================================

    private function getBrandId(array $productData): int
    {
        try {
            $specGroup = collect($productData['specificationGroup'] ?? [])
                ->firstWhere('groupName', 'Brand');

            if (empty($specGroup) || empty($specGroup['specifications'][0])) {
                return Config::get('services.alta.default_brand_id', 6);
            }

            $brandName = $specGroup['specifications'][0]['specificationMeaning'] ?? null;

            if (empty($brandName)) {
                return Config::get('services.alta.default_brand_id', 6);
            }

            return Cache::remember(
                'alta_brand_' . md5($brandName),
                now()->addMinutes(self::CACHE_DURATION_BRAND),
                function () use ($brandName) {
                    $brand = ProductBrand::whereHas('translations',
                        fn ($q) => $q->where('title', 'like', $brandName)
                    )->first();

                    return $brand->id ?? Config::get('services.alta.default_brand_id', 6);
                }
            );

        } catch (Exception $e) {
            Log::warning("⚠️  Error finding brand: {$e->getMessage()}");
            return Config::get('services.alta.default_brand_id', 6);
        }
    }

    // ============================================
    // Price
    // ============================================

    private function createPrice(Product $product, array $productData): void
    {
        try {
            $productPrice  = $productData['previousPrice'] ?? $productData['price'] ?? 0;
            $discountPrice = $productData['previousPrice'] ? $productData['price'] : null;

            ProductPrice::create([
                'product_id'       => $product->id,
                'dealer_price'     => $productPrice,
                'regular_price'    => $productPrice,
                'discount_price'   => $discountPrice,
                'discount_percent' => $productData['discountPercent'] ?? 0,
            ]);

        } catch (Exception $e) {
            Log::error("❌ Error creating price: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Translations
    // ============================================

    private function createTranslations(Product $product, array $productData): void
    {
        try {
            $locales     = ['ka', 'en', 'ru'];
            $productName = $this->sanitizeString($productData['name']) ?: 'Unnamed Product';
            $baseSlug    = Str::slug($productName) . "-{$product->id}";

            foreach ($locales as $locale) {
                ProductTranslation::create([
                    'product_id'  => $product->id,
                    'locale'      => $locale,
                    'title'       => $productName,
                    'slug'        => $baseSlug,
                    'description' => $locale === 'ka' ? $this->sanitizeString($productData['description'] ?? null) : null,
                    'keywords'    => null,
                ]);
            }

        } catch (Exception $e) {
            Log::error("❌ Error creating translations: {$e->getMessage()}");
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
            Log::error("❌ Error creating variations: {$e->getMessage()}");
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
                            'value'      => $this->sanitizeString($spec['specificationMeaning'] ?? null),
                            'filter'     => !empty($spec['specificationLinkedUrl']) ? 1 : 0,
                        ]);
                    }
                }
            }

        } catch (Exception $e) {
            Log::error("❌ Error creating specifications: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Images
    // ============================================

    private function downloadAndSaveImages(Product $product, array $productData): void
    {
        try {
            if (empty($productData['images'])) {
                return;
            }

            $images        = [];
            $processedUrls = [];
            $mainImageSet  = false;

            foreach ($productData['images'] as $index => $imageUrl) {
                if (in_array($imageUrl, $processedUrls)) {
                    Log::debug("⏭️  Skipping duplicate image: {$imageUrl}");
                    continue;
                }

                $processedUrls[] = $imageUrl;

                try {
                    $urlToFetch = $this->getScrapeUrl($imageUrl);
                    $response   = Http::timeout(30)
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                        ->get($urlToFetch);

                    if (!$response->successful()) {
                        Log::warning("⚠️  Failed to download image: {$urlToFetch}");
                        continue;
                    }
                    $imageSize = strlen($response->body());
                    $ext      = $this->getImageExtension($imageUrl);
                    $filename = Str::random(40) . '.' . $ext;
                    $path     = "uploads/products/{$product->id}/{$filename}";
                    Storage::disk('public')->put($path, $response->body());
                    if ($index === 0 && !$mainImageSet) {
                        $product->update(['main_image' => $path]);
                        $mainImageSet = true;
                    } else {
                        $images[] = [
                            'product_id' => $product->id,
                            'path'       => $path,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    Log::info("✅ Downloaded image: {$filename}");
                } catch (Exception $e) {
                    Log::warning("⚠️  Error downloading image: {$e->getMessage()}");
                    continue;
                }
            }

            if (!empty($images)) {
                ProductImage::insert($images);
                Log::info("📦 Bulk inserted " . count($images) . " images");
            }

            // ✅ გასწორდა: main image ვერ დაყენდა — null-ზე დავაყენოთ
            if (!$mainImageSet) {
                Log::warning("⚠️  Main image not set for product {$product->id}");
                $product->update(['main_image' => null]);
            }

        } catch (Exception $e) {
            Log::error("❌ Error downloading images: {$e->getMessage()}");
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

            $translator = new GoogleTranslation();
            $specs      = [];

            foreach ($productData['mainSpecification'] as $spec) {
                if (empty($spec['specificationName'])) {
                    continue;
                }

                $name  = $this->sanitizeString($spec['specificationName']);
                $value = $this->sanitizeString($spec['specificationMeaning'] ?? null);

                if (empty($name)) {
                    continue;
                }

                $translatedName = Cache::remember(
                    'alta_translation_' . md5($name),
                    now()->addMinutes(self::CACHE_DURATION_TRANSLATION),
                    fn () => $translator->translateToGeorgian($name)
                );

                $translatedValue = empty($value) ? '' : Cache::remember(
                    'alta_translation_' . md5($value),
                    now()->addMinutes(self::CACHE_DURATION_TRANSLATION),
                    fn () => $translator->translateToGeorgian($value)
                );

                $specs[] = [
                    'product_id' => $product->id,
                    'name'       => $translatedName,
                    'value'      => $translatedValue,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($specs)) {
                ProductShortSpecification::insert($specs);
            }

        } catch (Exception $e) {
            Log::error("❌ Error creating short specifications: {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // SOAP B2B Stock Check
    // ============================================

    protected function checkB2BStock($sku): array
    {
        try {
            $client = new SoapClient('http://extra.alta.com.ge/b2b/b2bEWS?WSDL', [
                'trace'              => 1,
                'exceptions'         => true,
                'encoding'           => 'UTF-8',
                'connection_timeout' => 30,
            ]);

            $result = $client->GetPriceList([
                'user'     => 'UNIPRO_ICH',
                'password' => 'CHI1457160',
                'item'     => intval($sku),
            ]);

            if (empty($result->PriceList) || empty($result->PriceList->items)) {
                Log::debug("⚠️  SOAP: No stock found for {$sku}");
                return [
                    'found'    => false,
                    'qty_text' => null,
                    'in_stock' => false,
                    'quantity' => 0,
                ];
            }

            $item = $result->PriceList->items->item;

            if (!is_array($item)) {
                $item = [$item];
            }

            $item = $item[0] ?? null;

            if (empty($item)) {
                return [
                    'found'    => false,
                    'qty_text' => null,
                    'in_stock' => false,
                    'quantity' => 0,
                ];
            }

            $qtyText   = (string) ($item->qty_text ?? '');
            $stockData = $this->parseQtyText($qtyText);

            Log::debug("✅ SOAP B2B: Found for {$sku}", [
                'qty_text' => $qtyText,
                'in_stock' => $stockData['in_stock'],
                'quantity' => $stockData['quantity'],
            ]);

            return [
                'found'    => true,
                'qty_text' => $qtyText,
                'in_stock' => $stockData['in_stock'],
                'quantity' => $stockData['quantity'],
            ];

        } catch (Exception $e) {
            Log::warning("⚠️  SOAP B2B error for {$sku}: {$e->getMessage()}");
            return [
                'found'    => false,
                'qty_text' => null,
                'in_stock' => false,
                'quantity' => 0,
                'error'    => $e->getMessage(),
            ];
        }
    }

    // ============================================
    // Parse qty_text
    // ============================================

    protected function parseQtyText(?string $qtyText): array
    {
        try {
            if (empty($qtyText)) {
                return ['in_stock' => false, 'quantity' => 0, 'text' => $qtyText];
            }

            $qtyText = strtolower(trim($qtyText));

            if (strpos($qtyText, '>=') === 0) {
                $minQty = (int) str_replace('>=', '', $qtyText);
                return ['in_stock' => true, 'quantity' => max($minQty, 10), 'text' => $qtyText];
            }

            if (strpos($qtyText, '>') === 0) {
                $qty = (int) preg_replace('/[^0-9]/', '', $qtyText);
                return ['in_stock' => $qty > 0, 'quantity' => $qty, 'text' => $qtyText];
            }

            if (is_numeric($qtyText)) {
                $qty = (int) $qtyText;
                return ['in_stock' => $qty > 0, 'quantity' => $qty, 'text' => $qtyText];
            }

            if (preg_match('/out|not|unavailable|უ/i', $qtyText)) {
                return ['in_stock' => false, 'quantity' => 0, 'text' => $qtyText];
            }

            if (preg_match('/in stock|available|ხელმ|აქვ/i', $qtyText)) {
                return ['in_stock' => true, 'quantity' => 5, 'text' => $qtyText];
            }

            return ['in_stock' => false, 'quantity' => 0, 'text' => $qtyText];

        } catch (Exception $e) {
            Log::warning("⚠️  qty_text parsing error: {$qtyText}");
            return ['in_stock' => false, 'quantity' => 0, 'text' => $qtyText];
        }
    }

    // ============================================
    // Helpers
    // ============================================

    private function getScrapeUrl(string $imageUrl): string
    {
        $token = Config::get('services.alta.scrape_token', '54ca3e2868ca407893b3316c254d6db6c146439c5b3');

        if (empty($token)) {
            return $imageUrl;
        }

        return "https://api.scrape.do/?url=" . urlencode($imageUrl) . "&token={$token}";
    }

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
            Log::warning("⚠️  Error getting image extension: {$e->getMessage()}");
            return 'jpg';
        }
    }
}