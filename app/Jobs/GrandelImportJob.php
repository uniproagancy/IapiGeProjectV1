<?php

namespace App\Jobs;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductBrandTranslation;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductTranslation;
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

class GrandelImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;
    public int $backoff = 30;

    private const SUPPLIER_ID      = 7;
    private const SKU_PREFIX       = 'GRANDEL-';
    private const DEFAULT_CATEGORY = 6;
    private const DEFAULT_BRAND    = 6;

    public function __construct(
        public array $row   // ['title','brand','model','price','old_price','color','description','image']
    ) {
    }

    public function handle(): void
    {
        $title = trim($this->row['title'] ?? '');
        $model = trim($this->row['model'] ?? '');

        if ($title === '' || $model === '') {
            Log::warning("⚠️ Grandel: title ან model ცარიელია — გამოტოვება");
            return;
        }

        $sku = self::SKU_PREFIX . $model;
        Log::info("🔄 Grandel job START: '{$title}' (sku={$sku})");

        try {
            $existing = Product::where('sku', $sku)->first();

            if ($existing && $existing->update_lock) {
                Log::info("🔒 Grandel: locked, skip — {$sku}");
                return;
            }

            DB::transaction(function () use ($sku, $existing, $title, $model) {
                $brandId   = $this->resolveBrand($this->row['brand'] ?? null);
                $price     = $this->parsePrice($this->row['price'] ?? null);
                $oldPrice  = $this->parsePrice($this->row['old_price'] ?? null);

                // ✅ category ექსელიდან (ციფრი), თუ ცარიელია → default
                $categoryId = !empty($this->row['category']) && is_numeric($this->row['category'])
                    ? (int) $this->row['category']
                    : self::DEFAULT_CATEGORY;

                if ($existing) {
                    $product = $existing;

                    $updateData = [
                        'in_stock' => 1,
                        'show'     => 1,
                        'active'   => 1,
                        'quantity' => 1,
                    ];

                    // 🏷️ brand + category მხოლოდ თუ taxonomy არ ჩაკეტილია
                    if (!$existing->taxonomy_lock) {
                        $updateData['brand_id']    = $brandId;
                        $updateData['category_id'] = $categoryId;
                    } else {
                        Log::info("🏷️ Grandel: taxonomy locked, brand/category უცვლელი — {$sku}");
                    }

                    $product->update($updateData);
                    Log::info("🔁 Grandel: updated {$sku} (id={$product->id}, cat={$categoryId})");
                } else {
                    $product = Product::create([
                        'supplier_product_id' => null,
                        'brand_id'            => $brandId,
                        'category_id'         => $categoryId,   // ✅ ექსელიდან
                        'sku'                 => $sku,
                        'supplier_id'         => self::SUPPLIER_ID,
                        'main_image'          => null,
                        'active'              => 1,
                        'quantity'            => 1,
                        'in_stock'            => 1,
                        'show'                => 1,
                    ]);
                    Log::info("✨ Grandel: created {$sku} (id={$product->id}, cat={$categoryId})");
                }

                // ფასი — discount თუ old_price > price
                $hasDiscount = $oldPrice && $oldPrice > $price;
                ProductPrice::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'regular_price'    => $hasDiscount ? $oldPrice : $price,
                        'dealer_price'     => $hasDiscount ? $oldPrice : $price,
                        'discount_price'   => $hasDiscount ? $price : null,
                        'discount_percent' => 0,
                    ]
                );

                // translations
                $slug = Str::slug($title, '-') . '-' . $product->id;
                $desc = $this->row['description'] ?? null;
                foreach (['ka', 'en', 'ru'] as $locale) {
                    ProductTranslation::updateOrCreate(
                        ['product_id' => $product->id, 'locale' => $locale],
                        [
                            'title'       => $title,
                            'slug'        => $slug,
                            'description' => $locale === 'ka' ? $desc : null,
                            'keywords'    => null,
                        ]
                    );
                }

                // სურათი
                if (!empty($this->row['image']) && empty($product->main_image)) {
                    $this->downloadImage($product, $this->row['image']);
                }

                Log::info("✅ Grandel saved: {$sku} (brand={$brandId})");
            });

        } catch (Exception $e) {
            Log::error("❌ Grandel job error [{$title}]: " . $e->getMessage());
            throw $e;
        }
    }

    private function parsePrice($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }
        $clean = preg_replace('/[^0-9.]/', '', (string) $value);
        return $clean === '' ? 0 : (float) $clean;
    }

    private function resolveBrand(?string $brandName): int
    {
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

        Log::info("✨ Grandel: new brand '{$brandName}' id={$newBrand->id}");
        return $newBrand->id;
    }

    private function downloadImage(Product $product, string $url): void
    {
        try {
            $resp = Http::timeout(30)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'Referer'    => 'https://grandel.ge/',
            ])->get($url);

            if (!$resp->successful()) {
                Log::warning("⚠️ Grandel image download failed: {$url}");
                return;
            }

            $ext      = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = Str::random(40) . '.' . strtolower($ext);
            $path     = "uploads/products/{$product->id}/{$filename}";

            Storage::disk('public')->put($path, $resp->body());
            $product->update(['main_image' => $path]);

            Log::info("📦 Grandel image saved for product {$product->id}");

        } catch (Exception $e) {
            Log::warning("⚠️ Grandel image: " . $e->getMessage());
        }
    }
}