<?php

namespace App\Jobs;

use App\Models\AltaID;
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
use App\Models\Product\ProductVariation;
use App\Models\Product\ProductVariationItem;
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

class AltaProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $productData         = [];
    protected array $productAvailability = [];

    public int $tries             = 3;
    public int $timeout           = 300;
    public int $maxExceptions     = 3;
    public int $backoffMultiplier = 2;

    private const CACHE_DURATION_BRAND = 24 * 60;
    private const MAX_IMAGE_SIZE       = 5 * 1024 * 1024;

    public function __construct(array $productData = [], array $productAvailability = [])
    {
        $this->productData         = $productData;
        $this->productAvailability = $productAvailability;
    }

    public function handle(): void
    {
        try {
            if (empty($this->productData) || empty($this->productData['id'])) {
                Log::warning('⚠️  AltaProductJob: productData is empty or missing id');
                return;
            }

            Log::info("🔄 Processing Alta product: {$this->productData['id']}", [
                'attempt' => $this->attempts(),
            ]);

            $this->saveProductWithVariants($this->productData, $this->productAvailability);

            Log::info("✅ Alta product saved: {$this->productData['id']}");

        } catch (Exception $e) {
            $productId = $this->productData['id'] ?? 'unknown';
            Log::error("❌ Error processing Alta product {$productId}: {$e->getMessage()}", [
                'attempt' => $this->attempts(),
                'trace'   => $e->getTraceAsString(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->getRetryDelay());
            } else {
                Log::critical("🚫 Alta job permanently failed: {$productId}", [
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
        Log::error("🚨 Alta job permanently failed for product {$productId}", [
            'error'    => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    public function saveProductWithVariants(array $productData, array $productAvailability): void
    {
        if (empty($productData['id'])) {
            throw new Exception('Product ID is required');
        }

        // DEBUG
        Log::info("🔍 Alta debug: id={$productData['id']} | barCode=" . ($productData['barCode'] ?? 'null'));
        Log::info("🔍 Alta availability: " . json_encode($productAvailability, JSON_UNESCAPED_UNICODE));

        $hasStock = $this->checkTbilisiStock($productAvailability);
        Log::info("🔍 Alta hasStock: " . ($hasStock ? 'true' : 'false'));

        if (!$hasStock) {
            Log::info("⏭️  Skipping Alta product (no Tbilisi stock): {$productData['id']}");
            return;
        }

        $barCode = $productData['barCode'] ?? $productData['id'];
        $sku     = 'ALTA-' . $barCode;

        $existing = Product::where('sku', $sku)->first();
        if ($existing && $existing->update_lock) {
            Log::info("🔒 Skipping locked Alta product: {$sku}");
            return;
        }

        $b2bStock = AltaID::where('product_id', (string) $barCode)->first();
        Log::info("🔍 Alta b2bStock: " . ($b2bStock ? "qty={$b2bStock->quantity}" : 'null — B2B ცხრილში არ არის'));

        $exists = (bool) $existing;

        if (!$b2bStock) {
            if ($exists) {
                Log::info("🔁 Alta: B2B-ში არ არის მაგრამ DB-შია — quantity=0 | {$sku}");
                $this->updateExistingProduct($productData, ['quantity' => 0], $sku);
            } else {
                Log::info("⏭️  Skipping Alta product (not in B2B list, not in DB): {$productData['id']}");
            }
            return;
        }

        if ($exists) {
            Log::info("🔁 Alta: განახლება | {$sku} | qty={$b2bStock->quantity}");
            $this->updateExistingProduct($productData, $b2bStock->toArray(), $sku);
        } else {
            if ($b2bStock->quantity >= 2) {
                Log::info("✨ Alta: ახალი პროდუქტი | {$sku} | qty={$b2bStock->quantity}");
                $this->createNewProduct($productData, $b2bStock->toArray(), $sku);
            } else {
                Log::info("⏭️  Skipping new Alta product (insufficient B2B stock qty={$b2bStock->quantity}): {$productData['id']}");
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

    private function getCategoryId(array $productData): int
    {
        $categoryName = $productData['categoryName'] ?? null;

        if ($categoryName) {
            $category = ProductCategory::whereRaw(
                'LOWER(TRIM(alta_category_name)) = ?',
                [mb_strtolower(trim($categoryName))]
            )->first();

            if ($category) {
                Log::info("✅ Alta category mapped: '{$categoryName}' → category_id={$category->id}");
                return $category->id;
            }
        }

        Log::warning("⚠️ Alta category not mapped: categoryName='{$categoryName}'");
        return 3;
    }

    private function updateExistingProduct(array $productData, array $b2bStock, string $sku): void
    {
        try {
            $product = Product::where('sku', $sku)->firstOrFail();

            DB::transaction(function () use ($product, $productData, $b2bStock, $sku) {
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

                $show     = $b2bStock['quantity'] >= 1 ? 1 : 0;
                $quantity = $b2bStock['quantity'] >= 1 ? $b2bStock['quantity'] : 0;
                $in_stock = $b2bStock['quantity'] >= 1 ? 1 : 0;

                $updateData = [
                    'quantity' => $quantity,
                    'in_stock' => $in_stock,
                    'show'     => $show,
                    'active'   => $show,
                ];

                if (!$product->taxonomy_lock) {
                    $updateData['category_id'] = $this->getCategoryId($productData);
                    $updateData['brand_id']    = $this->getBrandId($productData);
                } else {
                    Log::info("🏷️ Alta: taxonomy locked, category/brand უცვლელი — {$sku}");
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

                if (!empty($productData['images'])) {
                    $this->updateProductImages($product, $productData);
                }

                Log::info("🔁 Updated Alta product: {$product->id}, stock: {$quantity}");
            });

        } catch (Exception $e) {
            Log::error("❌ Error updating Alta product {$productData['id']}: {$e->getMessage()}");
            throw $e;
        }
    }

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
                    continue;
                }

                $processedUrls[] = $imageUrl;

                $path = $this->downloadImageViaWorker($product, $imageUrl);
                if (!$path) continue;

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
            }

            $oldImages = ProductImage::where('product_id', $product->id)->withTrashed()->get();
            foreach ($oldImages as $oldImage) {
                try {
                    if (!empty($oldImage->path) && Storage::disk('public')->exists($oldImage->path)) {
                        Storage::disk('public')->delete($oldImage->path);
                    }
                } catch (Exception $e) {
                    Log::warning("⚠️  Error deleting old image: {$e->getMessage()}");
                }
            }
            ProductImage::where('product_id', $product->id)->forceDelete();

            if (!empty($images)) {
                ProductImage::insert($images);
                Log::info("📦 Inserted " . count($images) . " images for product {$product->id}");
            }

            if (!$mainImageSet) {
                $product->update(['main_image' => null]);
            }

        } catch (Exception $e) {
            Log::error("❌ Error updating images: {$e->getMessage()}");
            throw $e;
        }
    }

    private function createNewProduct(array $productData, array $b2bStock, string $sku): void
    {
        DB::transaction(function () use ($productData, $b2bStock, $sku) {
            try {
                $brandId    = $this->getBrandId($productData);
                $categoryId = $this->getCategoryId($productData);
                $show       = $b2bStock['quantity'] >= 1 ? 1 : 0;
                $quantity   = $b2bStock['quantity'] >= 1 ? $b2bStock['quantity'] : 0;
                $in_stock   = $b2bStock['quantity'] >= 1 ? 1 : 0;

                $product = Product::create([
                    'supplier_product_id' => $productData['id'],
                    'brand_id'            => $brandId,
                    'category_id'         => $categoryId,
                    'sku'                 => $sku,
                    'supplier_id'         => 2,
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

                Log::info("✨ Created new Alta product: {$product->id}, category: {$categoryId}, brand: {$brandId}");

            } catch (Exception $e) {
                Log::error("❌ Error creating Alta product: {$e->getMessage()}");
                throw $e;
            }
        });
    }

    private function getBrandId(array $productData): int
    {
        try {
            $brandName = null;

            foreach ($productData['specificationGroup'] ?? [] as $group) {
                foreach ($group['specifications'] ?? [] as $spec) {
                    if (in_array($spec['specificationName'], ['Brand', 'ბრენდი', 'Бренд'])) {
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

            $normalized = mb_strtolower(trim($brandName));

            return Cache::remember(
                'alta_brand_' . md5($normalized),
                now()->addMinutes(self::CACHE_DURATION_BRAND),
                function () use ($brandName, $normalized) {
                    $brand = ProductBrand::whereHas('translations',
                        fn ($q) => $q->whereRaw('LOWER(TRIM(title)) = ?', [$normalized])
                    )->first();

                    if ($brand) {
                        Log::info("✅ Alta brand found: '{$brandName}' → brand_id={$brand->id}");
                        return $brand->id;
                    }

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

                    Log::info("✨ Alta: New brand created: '{$brandName}', id={$newBrand->id}");

                    return $newBrand->id;
                }
            );

        } catch (Exception $e) {
            Log::warning("⚠️ Error finding/creating Alta brand: {$e->getMessage()}");
            return 6;
        }
    }

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
            Log::error("❌ Error creating price for Alta product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

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
            Log::error("❌ Error creating translations for Alta product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

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
            Log::error("❌ Error creating variations for Alta product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

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
            Log::error("❌ Error creating specifications for Alta product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

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
                    continue;
                }

                $processedUrls[] = $imageUrl;

                $path = $this->downloadImageViaWorker($product, $imageUrl);
                if (!$path) continue;

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
            }

            if (!empty($images)) {
                ProductImage::insert($images);
                Log::info("📦 Bulk inserted " . count($images) . " images for product {$product->id}");
            }

            if (!$mainImageSet) {
                Log::warning("⚠️  Main image not set for product {$product->id}");
                $product->update(['main_image' => null]);
            }

        } catch (Exception $e) {
            Log::error("❌ Error downloading images for Alta product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    private function downloadImageViaWorker(Product $product, string $imageUrl): ?string
    {
        try {
            $workerUrl = env('ALTA_WORKER_URL', 'https://dry-king-29d3.royal-sunset-e1c6.workers.dev')
                . '?' . http_build_query([
                    'type' => 'image',
                    'url'  => $imageUrl,
                ]);

            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get($workerUrl);

            if (!$response->successful()) {
                Log::warning("⚠️  Image download failed via worker: HTTP={$response->status()} | {$imageUrl}");
                return null;
            }

            $imageSize = strlen($response->body()); // binary-safe
            if ($imageSize === 0) {
                Log::warning("⚠️  Image empty (0 bytes): {$imageUrl}");
                return null;
            }
            if ($imageSize > self::MAX_IMAGE_SIZE) {
                Log::warning("⚠️  Image too large ({$imageSize} bytes): {$imageUrl}");
                return null;
            }

            $ext      = $this->getImageExtension($imageUrl);
            $filename = Str::random(40) . '.' . $ext;
            $path     = "uploads/products/{$product->id}/{$filename}";

            Storage::disk('public')->put($path, $response->body());
            Log::info("📸 Image saved via worker: {$filename} | product_id={$product->id}");

            return $path;

        } catch (Exception $e) {
            Log::warning("⚠️  Error downloading image via worker: {$e->getMessage()}");
            return null;
        }
    }

    private function createShortSpecifications(Product $product, array $productData): void
    {
        try {
            if (empty($productData['mainSpecification'])) {
                return;
            }

            $specs = [];

            foreach ($productData['mainSpecification'] as $spec) {
                if (empty($spec['specificationName'])) {
                    continue;
                }

                $name  = $this->sanitizeString($spec['specificationName']);
                $value = $this->sanitizeString($spec['specificationMeaning'] ?? null);

                if (empty($name)) {
                    continue;
                }

                $specs[] = [
                    'product_id' => $product->id,
                    'name'       => $name,
                    'value'      => $value ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($specs)) {
                ProductShortSpecification::insert($specs);
            }

        } catch (Exception $e) {
            Log::error("❌ Error creating short specifications for Alta product {$product->id}: {$e->getMessage()}");
            throw $e;
        }
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