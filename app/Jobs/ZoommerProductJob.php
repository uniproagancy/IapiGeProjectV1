<?php

namespace App\Jobs;

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

    private function getCategoryId(array $productData): int
    {
        $categoryName = $productData['categoryName'] ?? null;

        if ($categoryName) {
            $category = ProductCategory::whereRaw(
                'LOWER(TRIM(zoommer_category_name)) = ?',
                [mb_strtolower(trim($categoryName))]
            )->first();

            if ($category) {
                Log::info("✅ Zoommer category mapped: '{$categoryName}' → category_id={$category->id}");
                return $category->id;
            }
        }

        Log::warning("⚠️ Zoommer category not mapped: categoryName='{$categoryName}'");
        return 4;
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
                Log::info("⚠️ Zoommer brand not found in data, using default brand_id=6");
                return 6;
            }

            $normalized = mb_strtolower(trim($brandName));

            $brand = ProductBrand::whereHas('translations',
                fn ($q) => $q->whereRaw('LOWER(TRIM(title)) = ?', [$normalized])
            )->first();

            if ($brand) {
                Log::info("✅ Zoommer brand found: '{$brandName}' → brand_id={$brand->id}");
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

            Log::info("✨ Zoommer: New brand created: '{$brandName}', brand_id={$newBrand->id}");

            return $newBrand->id;

        } catch (Exception $e) {
            Log::warning("⚠️ Error finding/creating Zoommer brand: {$e->getMessage()}");
            return 6;
        }
    }

    public function saveProductWithVariants(array $productData, array $productAvailability): void
    {
        if (empty($productData['id'])) {
            throw new Exception('Product ID is required');
        }

        $hasStock = $this->checkTbilisiStock($productAvailability);

        $zoomId = $productData['id'];
        $sku    = $productData['barCode'] ?? ('ZOOM-' . $zoomId);

        // ძებნა supplier_product_id-ით — ძირითადი პროდუქტი
        $primary = Product::where('supplier_id', 4)
            ->where('supplier_product_id', $zoomId)
            ->first();

        // fallback: ძველი ჩანაწერები რომლებსაც supplier_product_id არ აქვთ
        if (!$primary) {
            $primary = Product::where('sku', $sku)
                ->where('supplier_id', 4)
                ->whereNull('supplier_product_id')
                ->first();

            if ($primary) {
                $primary->update(['supplier_product_id' => $zoomId]);
                Log::info("🔧 Zoommer: supplier_product_id დაემატა id={$primary->id}, zoom_id={$zoomId}");
            }
        }

        // ყველა პროდუქტი ამ SKU-ით (ერთნაირი barCode)
        $allWithSameSku = Product::where('sku', $sku)
            ->where('supplier_id', 4)
            ->get();

        if ($allWithSameSku->isEmpty() && !$primary) {
            // ახალი პროდუქტი
            if ($hasStock) {
                $this->createNewProduct($productData, $hasStock, $sku);
            } else {
                Log::info("⏭️  Skipping new product (no Tbilisi stock): {$zoomId}");
            }
            return;
        }

        // ძირითადი პროდუქტი სრულად განახლდება (specs, images, translations)
        if ($primary && !$primary->update_lock) {
            $this->updateExistingProduct($productData, $hasStock, $sku);
        } elseif ($primary && $primary->update_lock) {
            Log::info("🔒 Zoommer: primary locked, skip full update — {$sku}");
        }

        // ყველა დუბლიკატი — მხოლოდ ფასი და მარაგი განახლდება
        $duplicates = $allWithSameSku->where('id', '!=', optional($primary)->id);
        if ($duplicates->isNotEmpty()) {
            $this->updatePriceAndStock($productData, $hasStock, $duplicates);
        }
    }

    private function updatePriceAndStock(array $productData, bool $hasStock, $products): void
    {
        $productPrice  = (float) ($productData['previousPrice'] ?? $productData['price'] ?? 0);
        $discountPrice = $productData['previousPrice'] ? (float) $productData['price'] : null;

        $productPrice  = $this->calculatePrice($productPrice);
        $discountPrice = $discountPrice ? $this->calculatePrice($discountPrice) : null;

        foreach ($products as $product) {
            if ($product->update_lock) {
                Log::info("🔒 Zoommer: duplicate locked, skip — id={$product->id}");
                continue;
            }

            $product->update([
                'quantity' => $hasStock ? 5 : 0,
                'in_stock' => $hasStock ? 1 : 0,
                'show'     => $hasStock ? 1 : 0,
                'active'   => $hasStock ? 1 : 0,
            ]);

            ProductPrice::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'dealer_price'     => $productPrice,
                    'regular_price'    => $productPrice,
                    'discount_price'   => $discountPrice,
                    'discount_percent' => $productData['discountPercent'] ?? 0,
                ]
            );

            Log::info("🔄 Zoommer: duplicate განახლდა id={$product->id} (sku={$product->sku}, stock=" . ($hasStock ? 'yes' : 'no') . ")");
        }
    }

    private function checkTbilisiStock($availability): bool
    {
        $stores = collect($availability)
            ->filter(fn($store) =>
                ($store['city'] ?? '') === 'თბილისი'
                && str_contains($store['branchName'] ?? '', 'წერეთლის ფილიალი')
            );

        if ($stores->isEmpty()) {
            Log::info("🏬 Zoommer stock: წერეთლის ფილიალი ვერ მოიძებნა availability-ში");
            return false;
        }

        $inStock = $stores->contains(fn($store) => ($store['inStock'] ?? false) === true);

        $stores->each(function ($store) {
            Log::info("🏬 Zoommer stock [{$store['branchName']}]: " .
                (($store['inStock'] ?? false) ? '✅ მარაგშია' : '❌ არ არის'));
        });

        Log::info("🏬 Zoommer checkTbilisiStock შედეგი: " . ($inStock ? 'true (მარაგშია)' : 'false'));

        return $inStock;
    }

    private function updateExistingProduct(array $productData, bool $hasStock, string $sku): void
    {
        DB::transaction(function () use ($productData, $hasStock, $sku) {
            try {
                $product = Product::where('sku', $sku)->firstOrFail();

                $productPrice  = (float) ($productData['previousPrice'] ?? $productData['price'] ?? 0);
                $discountPrice = $productData['previousPrice'] ? (float) $productData['price'] : null;

                $productPrice  = $this->calculatePrice($productPrice);
                $discountPrice = $discountPrice ? $this->calculatePrice($discountPrice) : null;

                // ===== ფასი — ყოველთვის ახლდება =====
                $oldPrice = $product->price?->regular_price;
                $oldDiscount = $product->price?->discount_price;

                ProductPrice::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'dealer_price'     => $productPrice,
                        'regular_price'    => $productPrice,
                        'discount_price'   => $discountPrice,
                        'discount_percent' => $productData['discountPercent'] ?? 0,
                    ]
                );

                Log::info("💰 Zoommer ფასი განახლდა [{$sku}]: regular {$oldPrice} → {$productPrice} | discount {$oldDiscount} → " . ($discountPrice ?? 'null'));

                $updateData = [
                    'quantity' => $hasStock ? 5 : 0,
                    'in_stock' => $hasStock ? 1 : 0,
                    'show'     => $hasStock ? 1 : 0,
                    'active'   => $hasStock ? 1 : 0,
                ];

                if (!$product->taxonomy_lock) {
                    $updateData['category_id'] = $this->getCategoryId($productData);
                    $updateData['brand_id']    = $this->getBrandId($productData);
                } else {
                    Log::info("🏷️ Zoommer: taxonomy locked, category/brand უცვლელი — {$sku}");
                }

                $product->update($updateData);

                // ===== Short Specs =====
                ProductShortSpecification::where('product_id', $product->id)->forceDelete();
                $this->createShortSpecifications($product, $productData);

                // ===== Full Specs =====
                $sectionIds = ProductFullSpecificationSection::where('product_id', $product->id)->pluck('id');
                ProductFullSpecificationItem::whereIn('section_id', $sectionIds)->forceDelete();
                ProductFullSpecificationSection::where('product_id', $product->id)->forceDelete();
                $this->createFullSpecifications($product, $productData);

                if (!empty($productData['description'])) {
                    ProductTranslation::where('product_id', $product->id)
                        ->where('locale', 'ka')
                        ->update(['description' => $productData['description']]);
                }

                Log::info("🔁 Updated product: {$product->id}, stock: " . ($hasStock ? 'yes' : 'no'));

            } catch (Exception $e) {
                Log::error("Error updating product {$productData['id']}: {$e->getMessage()}");
                throw $e;
            }
        });
    }

    private function createNewProduct(array $productData, bool $hasStock, string $sku): void
    {
        DB::transaction(function () use ($productData, $hasStock, $sku) {
            try {
                $brandId    = $this->getBrandId($productData);
                $categoryId = $this->getCategoryId($productData);

                $product = Product::create([
                    'supplier_product_id' => $productData['id'],
                    'brand_id'            => $brandId,
                    'category_id'         => $categoryId,
                    'sku'                 => $sku,
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

                Log::info("✨ Created new product: {$product->id}, category: {$categoryId}, brand: {$brandId}");

            } catch (Exception $e) {
                Log::error("Error creating product {$productData['id']}: {$e->getMessage()}");
                throw $e;
            }
        });
    }

    private function createPrice(Product $product, array $productData): void
    {
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
    }

    private function createTranslations(Product $product, array $productData): void
    {
        $name     = $productData['name'] ?? 'Unnamed Product';
        $baseSlug = Str::slug($name, '-') . '-' . $product->id;

        foreach (['ka', 'en', 'ru'] as $locale) {
            ProductTranslation::create([
                'product_id'  => $product->id,
                'locale'      => $locale,
                'title'       => $name,
                'slug'        => $baseSlug,
                'description' => $locale === 'ka' ? ($productData['description'] ?? null) : null,
                'keywords'    => null,
            ]);
        }
    }

    private function createVariations(Product $product, array $productData): void
    {
        if (empty($productData['keySpecification'])) return;

        foreach ($productData['keySpecification'] as $specification) {
            if (empty($specification['specificationName'])) continue;

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
    }

    private function createFullSpecifications(Product $product, array $productData): void
    {
        if (empty($productData['specificationGroup'])) return;

        foreach ($productData['specificationGroup'] as $specificationGroup) {
            if (empty($specificationGroup['groupName'])) continue;

            $section = ProductFullSpecificationSection::create([
                'product_id' => $product->id,
                'name'       => $specificationGroup['groupName'],
            ]);

            foreach ($specificationGroup['specifications'] ?? [] as $spec) {
                if (empty($spec['specificationName'])) continue;

                ProductFullSpecificationItem::create([
                    'section_id' => $section->id,
                    'name'       => $spec['specificationName'],
                    'value'      => $spec['specificationMeaning'] ?? null,
                    'filter'     => !empty($spec['specificationLinkedUrl']) ? 1 : 0,
                ]);
            }
        }
    }

    private function createShortSpecifications(Product $product, array $productData): void
    {
        if (empty($productData['mainSpecification'])) return;

        $specs = [];

        foreach ($productData['mainSpecification'] as $spec) {
            if (empty($spec['specificationName'])) continue;

            $specs[] = [
                'product_id' => $product->id,
                'name'       => mb_substr($spec['specificationName'] ?? '', 0, 255),
                'value'      => mb_substr($spec['specificationMeaning'] ?? '', 0, 255), // ← FIX
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($specs)) {
            ProductShortSpecification::insert($specs);
        }
    }

    private function downloadImages(Product $product, array $productData): void
    {
        if (empty($productData['images'])) return;

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

            } catch (Exception $e) {
                Log::warning("Error downloading image {$imageUrl}: {$e->getMessage()}");
            }
        }
    }

    protected function getImageExtension(string $url): string
    {
        try {
            $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            return in_array(strtolower($ext), $validExtensions) ? strtolower($ext) : 'jpg';
        } catch (Exception $e) {
            return 'jpg';
        }
    }
}