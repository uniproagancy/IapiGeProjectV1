<?php

namespace App\Jobs;

use App\Models\EliteProduct;
use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductBrandTranslation;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductFullSpecificationItem;
use App\Models\Product\ProductFullSpecificationSection;
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
    private const FALLBACK_BRAND_ID    = 1;
    private const FALLBACK_CATEGORY_ID = 203;
    private const SHORT_SPEC_LIMIT     = 5;
    private const SKU_PREFIX           = 'ELIT-';
    private const CACHE_DURATION_BRAND = 24 * 60;

    public function __construct(
        public array $productData,
        public int   $eliteProductId,
        public array $availabilityInStores = []
    ) {}

    public function handle(): void
    {
        @set_time_limit(0);

        try {
            $product = $this->productData;
            $barCode = (string) ($product['barCode'] ?? '');
            $name    = trim((string) ($product['name'] ?? ''));

            if (!$barCode || !$name) {
                Log::warning("⛔ Elite: name/barCode ცარიელია");
                return;
            }

            // 1. თბილისში ნაშთის შემოწმება
            if (!$this->hasTbilisiStock($this->availabilityInStores)) {
                Log::info("⏭️ Elite: თბილისში მარაგი არ არის, skip", ['barCode' => $barCode]);
                return;
            }

            // 4. SKU = ELIT- + BarCode
            $sku = self::SKU_PREFIX . $barCode;

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

            // 3. კატეგორია და ბრენდი
            $categoryId = $this->resolveCategoryId(
                (int)    ($product['categoryId']   ?? 0),
                (string) ($product['categoryName'] ?? '')
            );
            $brandId = $this->resolveBrandId($product);

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

            // ============ Product upsert ============
            $existingProduct = Product::where('sku', $sku)->first();
            $isNew           = !$existingProduct;

            if ($isNew) {
                $existingProduct = Product::create([
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

                Log::info("➕ Elite: ახალი პროდუქტი", ['sku' => $sku, 'id' => $existingProduct->id]);

            } else {
                $updateData = [
                    'quantity' => $inStock ? max($quantity, 1) : 0,
                    'in_stock' => $inStock,
                    'show'     => $inStock,
                ];

                if (!$existingProduct->taxonomy_lock) {
                    $updateData['brand_id']    = $brandId;
                    $updateData['category_id'] = $categoryId;
                }

                $existingProduct->update($updateData);
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

            // Short specs
            if ($isNew) {
                $shortSpecs = $this->extractShortSpecs($product);
                if (!empty($shortSpecs)) {
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

                // Variations
                $this->createVariations($existingProduct, $product);
            } else {
                // Update-ზე short specs და full specs განახლდება
                ProductShortSpecification::where('product_id', $existingProduct->id)->forceDelete();
                $shortSpecs = $this->extractShortSpecs($product);
                if (!empty($shortSpecs)) {
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
            }

            // Full specifications (create და update ორივეზე განახლდება)
            $this->syncFullSpecifications($existingProduct, $product);

            // Main image
            if (empty($existingProduct->main_image) && !empty($images)) {
                $localPath = $this->downloadImage($images[0], $existingProduct->id);
                if ($localPath) {
                    $existingProduct->update(['main_image' => $localPath]);
                    Log::info("📸 Elite: main_image შენახულია", ['sku' => $sku, 'path' => $localPath]);
                }
            }

            // EliteProduct synced
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

    // ─────────────────────────────────────────────
    //  1. თბილისში ნაშთი  (city = "Tbilisi")
    // ─────────────────────────────────────────────
    private function hasTbilisiStock(array $availability): bool
    {
        if (empty($availability)) {
            return false;
        }

        return collect($availability)
            ->where('city', 'თბილისი')           // Elite-ში ინგლისურად
            ->contains(fn($store) => $store['inStock'] === true);
    }

    // ─────────────────────────────────────────────
    //  3. კატეგორიის mapping
    // ─────────────────────────────────────────────
    private function resolveCategoryId(int $eliteCategoryId, string $eliteCategoryName): int
    {
        if (!$eliteCategoryId) {
            return self::FALLBACK_CATEGORY_ID;
        }

        $category = ProductCategory::where('elite_category_id', $eliteCategoryId)->first();

        if ($category) {
            if ($eliteCategoryName && $category->elite_category_name !== $eliteCategoryName) {
                $category->update(['elite_category_name' => $eliteCategoryName]);
            }
            return $category->id;
        }

        Log::info("📋 Elite: დაუმაპავი კატეგორია", [
            'elite_category_id'   => $eliteCategoryId,
            'elite_category_name' => $eliteCategoryName,
        ]);

        return self::FALLBACK_CATEGORY_ID;
    }

    // ─────────────────────────────────────────────
    //  3. ბრენდის პოვნა / შექმნა
    // ─────────────────────────────────────────────
    private function resolveBrandId(array $product): int
    {
        // Elite JSON-ში brandName პირდაპირ არის, სპეციფიკაციებშიც "ბრენდი"
        $brandName = null;

        // პირველ რიგში specificationGroup-ში ვეძებთ
        foreach ($product['specificationGroup'] ?? [] as $group) {
            foreach ($group['specifications'] ?? [] as $spec) {
                if (in_array($spec['specificationName'], ['ბრენდი', 'Brand', 'Бренд'])) {
                    $brandName = $spec['specificationMeaning'] ?? null;
                    break 2;
                }
            }
        }

        // fallback — პირდაპირ brandName field-ი
        if (empty($brandName)) {
            $brandName = $product['brandName'] ?? null;
        }

        if (empty($brandName)) {
            return self::FALLBACK_BRAND_ID;
        }

        $normalized = mb_strtolower(trim($brandName));

        return Cache::remember(
            'elite_brand_' . md5($normalized),
            now()->addMinutes(self::CACHE_DURATION_BRAND),
            function () use ($brandName, $normalized) {
                $brand = ProductBrand::whereHas('translations',
                    fn($q) => $q->whereRaw('LOWER(TRIM(title)) = ?', [$normalized])
                )->first();

                if ($brand) {
                    Log::info("✅ Elite: ბრენდი ნაპოვნია", ['brand' => $brandName, 'id' => $brand->id]);
                    return $brand->id;
                }

                $newBrand = ProductBrand::create(['active' => 1, 'show' => 1]);

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

                Log::info("✨ Elite: ახალი ბრენდი შექმნილია", ['brand' => $brandName, 'id' => $newBrand->id]);

                return $newBrand->id;
            }
        );
    }

    // ─────────────────────────────────────────────
    //  Full specifications — sync (create + update)
    // ─────────────────────────────────────────────
    private function syncFullSpecifications(Product $product, array $productData): void
    {
        if (empty($productData['specificationGroup'])) {
            return;
        }

        // ძველი წავშალოთ
        $sectionIds = ProductFullSpecificationSection::where('product_id', $product->id)->pluck('id');
        if ($sectionIds->isNotEmpty()) {
            ProductFullSpecificationItem::whereIn('section_id', $sectionIds)->forceDelete();
            ProductFullSpecificationSection::where('product_id', $product->id)->forceDelete();
        }

        // ახლიდან შევქმნათ
        foreach ($productData['specificationGroup'] as $group) {
            $groupName = trim((string) ($group['groupName'] ?? ''));
            if (!$groupName) {
                continue;
            }

            $section = ProductFullSpecificationSection::create([
                'product_id' => $product->id,
                'name'       => $groupName,
            ]);

            foreach ($group['specifications'] ?? [] as $spec) {
                $specName  = trim((string) ($spec['specificationName']  ?? ''));
                $specValue = trim((string) ($spec['specificationMeaning'] ?? ''));

                if (!$specName) {
                    continue;
                }

                ProductFullSpecificationItem::create([
                    'section_id' => $section->id,
                    'name'       => $specName,
                    'value'      => $specValue ?: null,
                    // Elite JSON-ში specificationLinkedUrl არ არის, filter = 0
                    'filter'     => 0,
                ]);
            }

            Log::info("📑 Elite: spec section შენახულია", [
                'product_id' => $product->id,
                'section'    => $groupName,
                'items'      => count($group['specifications'] ?? []),
            ]);
        }
    }

    // ─────────────────────────────────────────────
    //  2. Variations (keySpecification-დან)
    // ─────────────────────────────────────────────
    private function createVariations(Product $product, array $productData): void
    {
        if (empty($productData['keySpecification'])) {
            return;
        }

        foreach ($productData['keySpecification'] as $specification) {
            $specName = trim((string) ($specification['specificationName'] ?? ''));
            if (!$specName) {
                continue;
            }

            $variation = ProductVariation::create([
                'product_id' => $product->id,
                'name'       => $specName,
                'value'      => $specification['specificationMeaning'] ?? null,
            ]);

            foreach ($specification['specificationMeaningsList'] ?? [] as $item) {
                ProductVariationItem::create([
                    'variation_id'        => $variation->id,
                    'is_color'            => !empty($item['isColor']) ? 1 : 0,
                    'supplier_product_id' => $item['productId'] ?? null,
                    'value'               => $item['value'] ?? null,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────
    //  Short specs
    // ─────────────────────────────────────────────
    private function extractShortSpecs(array $product): array
    {
        $specs = [];

        if (!empty($product['mainSpecification']) && is_array($product['mainSpecification'])) {
            foreach ($product['mainSpecification'] as $spec) {
                $key   = trim((string) ($spec['specificationName']  ?? ''));
                $value = trim((string) ($spec['specificationMeaning'] ?? ''));
                if ($key && $value && !isset($specs[$key])) {
                    $specs[$key] = $value;
                    if (count($specs) >= self::SHORT_SPEC_LIMIT) return $specs;
                }
            }
        }

        if (!empty($product['specificationGroup']) && is_array($product['specificationGroup'])) {
            foreach ($product['specificationGroup'] as $group) {
                foreach ($group['specifications'] ?? [] as $spec) {
                    $key   = trim((string) ($spec['specificationName']  ?? ''));
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

    // ─────────────────────────────────────────────
    //  სურათის ჩამოტვირთვა
    // ─────────────────────────────────────────────
    private function downloadImage(string $url, int $productId): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Referer'    => 'https://ee.ge/',
                ])
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $ext = strtolower(
                pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg'
            );
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $ext = 'jpg';
            }

            $filename = 'main_' . time() . '.' . $ext;
            $path     = "uploads/products/{$productId}/{$filename}";

            Storage::disk('public')->put($path, $response->body());

            return $path;

        } catch (\Throwable $e) {
            Log::warning("⚠️ Elite: სურათი ვერ ჩამოიტვირთა", [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}