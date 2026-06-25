<?php

namespace App\Jobs;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductBrandTranslation;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductPrice;
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

class AllmarketProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    private const SUPPLIER_ID   = 13;
    private const DEFAULT_BRAND = 1;
    private const DEFAULT_CAT   = 4;
    private const SKU_PREFIX    = 'ALLMARKET-';

    public function __construct(public array $productData) {}

    public function handle(): void
    {
        $productCode = $this->productData['productCode'] ?? null;
        $id          = $this->productData['id'] ?? null;

        if (!$productCode || !$id) {
            Log::warning("⚠️ Allmarket: productCode ან id ცარიელია", $this->productData);
            return;
        }

        $sku = self::SKU_PREFIX . $productCode;

        Log::info("🔄 Allmarket: processing | id={$id} | sku={$sku}");

        try {
            $product = Product::where('sku', $sku)
                ->where('supplier_id', self::SUPPLIER_ID)
                ->first();

            $quantity = (int) ($this->productData['quantity'] ?? 0);
            $inStock  = $quantity > 0 ? 1 : 0;

            // ფასი — rrp
            $regularPrice  = (float) ($this->productData['rrp']['original']    ?? 0);
            $discountPrice = (float) ($this->productData['rrp']['discounted']  ?? 0);
            $hasDiscount   = $discountPrice > 0 && $discountPrice < $regularPrice;

            $discountPercent = $hasDiscount
                ? (int) round((($regularPrice - $discountPrice) / $regularPrice) * 100)
                : 0;

            if ($product) {
                // update_lock შემოწმება
                if ($product->update_lock) {
                    Log::info("🔒 Allmarket: locked, skip | sku={$sku}");
                    return;
                }

                // განახლება
                $updateData = [
                    'quantity' => $quantity,
                    'in_stock' => $inStock,
                    'show'     => $inStock,
                    'active'   => 1,
                ];

                if (!$product->taxonomy_lock) {
                    $updateData['brand_id']    = $this->getBrandId();
                    $updateData['category_id'] = $this->getCategoryId();
                }

                $product->update($updateData);

                ProductPrice::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'dealer_price'     => $regularPrice,
                        'regular_price'    => $regularPrice,
                        'discount_price'   => $hasDiscount ? $discountPrice : null,
                        'discount_percent' => $discountPercent,
                    ]
                );

                // სათაური განახლება
                ProductTranslation::where('product_id', $product->id)
                    ->where('locale', 'ka')
                    ->update(['title' => $this->productData['title'] ?? '']);

                Log::info("🔄 Allmarket: განახლდა | sku={$sku} | id={$product->id} | stock={$quantity}");

            } else {
                // ახალი პროდუქტი
                $brandId    = $this->getBrandId();
                $categoryId = $this->getCategoryId();

                $product = Product::create([
                    'sku'                 => $sku,
                    'supplier_id'         => self::SUPPLIER_ID,
                    'supplier_product_id' => (string) $id,
                    'brand_id'            => $brandId,
                    'category_id'         => $categoryId,
                    'quantity'            => $quantity,
                    'in_stock'            => $inStock,
                    'show'                => $inStock,
                    'active'              => 1,
                    'main_image'          => null,
                    'update_lock'         => 0,
                    'taxonomy_lock'       => 0,
                ]);

                // ფასი
                ProductPrice::create([
                    'product_id'       => $product->id,
                    'dealer_price'     => $regularPrice,
                    'regular_price'    => $regularPrice,
                    'discount_price'   => $hasDiscount ? $discountPrice : null,
                    'discount_percent' => $discountPercent,
                ]);

                // translation
                $title    = $this->productData['title'] ?? 'Unnamed';
                $baseSlug = Str::slug($title) . '-' . $product->id;

                foreach (['ka', 'en'] as $locale) {
                    ProductTranslation::create([
                        'product_id'  => $product->id,
                        'locale'      => $locale,
                        'title'       => $title,
                        'slug'        => $baseSlug . ($locale === 'en' ? '-en' : ''),
                        'description' => $this->productData['descrption'] ?? null,
                    ]);
                }

                // სურათი
                $this->downloadMainImage($product);

                Log::info("✨ Allmarket: ახალი პროდუქტი | sku={$sku} | id={$product->id} | cat={$categoryId} | brand={$brandId}");
            }

        } catch (\Throwable $e) {
            Log::error("❌ Allmarket: შეცდომა | sku={$sku} | " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    private function getBrandId(): int
    {
        try {
            $brandName = $this->productData['brand']['title'] ?? null;

            if (empty($brandName)) {
                return self::DEFAULT_BRAND;
            }

            $normalized = mb_strtolower(trim($brandName));

            $brand = ProductBrand::whereHas('translations',
                fn ($q) => $q->whereRaw('LOWER(TRIM(title)) = ?', [$normalized])
            )->first();

            if ($brand) {
                return $brand->id;
            }

            // ახალი ბრენდი
            $newBrand = ProductBrand::create(['active' => 1, 'show' => 1]);

            foreach (['ka', 'en'] as $locale) {
                ProductBrandTranslation::create([
                    'product_brand_id' => $newBrand->id,
                    'locale'           => $locale,
                    'title'            => $brandName,
                    'slug'             => Str::slug($brandName) . '-' . $newBrand->id . ($locale === 'en' ? '-en' : ''),
                ]);
            }

            Log::info("✨ Allmarket: ახალი ბრენდი '{$brandName}' | brand_id={$newBrand->id}");
            return $newBrand->id;

        } catch (\Throwable $e) {
            Log::warning("⚠️ Allmarket: ბრენდი ვერ მოიძებნა/შეიქმნა | " . $e->getMessage());
            return self::DEFAULT_BRAND;
        }
    }

    private function getCategoryId(): int
    {
        try {
            $categoryName = $this->productData['categories'][0]['title'] ?? null;

            if (empty($categoryName)) {
                return self::DEFAULT_CAT;
            }

            $category = ProductCategory::whereRaw(
                'LOWER(TRIM(allmarket_category_name)) = ?',
                [mb_strtolower(trim($categoryName))]
            )->first();

            if ($category) {
                Log::info("✅ Allmarket category mapped: '{$categoryName}' → id={$category->id}");
                return $category->id;
            }

            Log::warning("⚠️ Allmarket category not mapped: '{$categoryName}'");
            return self::DEFAULT_CAT;

        } catch (\Throwable $e) {
            Log::warning("⚠️ Allmarket: კატეგორია ვერ მოიძებნა | " . $e->getMessage());
            return self::DEFAULT_CAT;
        }
    }

    private function downloadMainImage(Product $product): void
    {
        $imageUrl = $this->productData['images'][0] ?? null;

        if (empty($imageUrl)) return;

        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get($imageUrl);

            if (!$response->successful()) {
                Log::warning("⚠️ Allmarket: სურათი ვერ ჩამოიტვირთა | {$imageUrl}");
                return;
            }

            $ext      = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg');
            $ext      = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) ? $ext : 'jpg';
            $filename = 'main_' . time() . '.' . $ext;
            $path     = "uploads/products/{$product->id}/{$filename}";

            Storage::disk('public')->put($path, $response->body());
            $product->update(['main_image' => $path]);

            Log::info("📸 Allmarket: სურათი შენახულია | sku={$product->sku}");

        } catch (\Throwable $e) {
            Log::warning("⚠️ Allmarket: სურათის შეცდომა | " . $e->getMessage());
        }
    }
}