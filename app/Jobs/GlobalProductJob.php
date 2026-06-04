<?php

namespace App\Jobs;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductBrandTranslation;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductTranslation;
use App\Services\Products\GlobalService;
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

class GlobalProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;
    public int $backoff = 30;

    private const SUPPLIER_ID      = 5;
    private const SKU_PREFIX       = 'GLOBAL-';
    private const DEFAULT_CATEGORY = 6;
    private const DEFAULT_BRAND    = 6;

    public function __construct(public string $name)
    {
    }

    public function handle(): void
    {
        try {
            $data = (new GlobalService())->searchByName($this->name);

            if (!$data || empty($data['sku'])) {
                Log::warning("⚠️ Global job: ვერ მოიძებნა — {$this->name}");
                return;
            }

            $sku = self::SKU_PREFIX . $data['sku'];

            // 🔒 lock — ჩაკეტილს არ ვეხებით
            $existing = Product::where('sku', $sku)->first();
            if ($existing && $existing->update_lock) {
                Log::info("🔒 Global job: locked, skip — {$sku}");
                return;
            }

            DB::transaction(function () use ($data, $sku, $existing) {
                $brandId    = $this->resolveBrand($data['brand'] ?? null);
                $categoryId = $this->resolveCategory($data['category'] ?? null);
                $hasStock   = ($data['stock'] ?? 0) > 0;

                if ($existing) {
                    $product = $existing;
                    $product->update([
                        'brand_id'    => $brandId,
                        'category_id' => $categoryId,
                        'quantity'    => $hasStock ? 5 : 0,
                        'in_stock'    => $hasStock ? 1 : 0,
                        'show'        => $hasStock ? 1 : 0,
                        'active'      => $hasStock ? 1 : 0,
                    ]);
                } else {
                    $product = Product::create([
                        'supplier_product_id' => null,
                        'brand_id'            => $brandId,
                        'category_id'         => $categoryId,
                        'sku'                 => $sku,
                        'supplier_id'         => self::SUPPLIER_ID,
                        'main_image'          => null,
                        'active'              => $hasStock ? 1 : 0,
                        'quantity'            => $hasStock ? 5 : 0,
                        'in_stock'            => $hasStock ? 1 : 0,
                        'show'                => $hasStock ? 1 : 0,
                    ]);
                }

                // ფასი — regular = price
                ProductPrice::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'regular_price'    => (float) ($data['price'] ?? 0),
                        'dealer_price'     => (float) ($data['price'] ?? 0),
                        'discount_price'   => !empty($data['old_price']) ? (float) $data['price'] : null,
                        'discount_percent' => 0,
                    ]
                );

                // translations
                $name = $data['name'] ?? $this->name;
                $slug = Str::slug($name, '-') . '-' . $product->id;
                foreach (['ka', 'en', 'ru'] as $locale) {
                    ProductTranslation::updateOrCreate(
                        ['product_id' => $product->id, 'locale' => $locale],
                        [
                            'title'       => $name,
                            'slug'        => $slug,
                            'description' => $locale === 'ka' ? ($data['description'] ?? null) : null,
                            'keywords'    => null,
                        ]
                    );
                }

                // სურათები (მხოლოდ ახალ პროდუქტზე ან თუ main_image ცარიელია)
                if (!empty($data['images']) && empty($product->main_image)) {
                    $this->downloadImages($product, $data['images']);
                }

                Log::info("✅ Global saved: {$sku} (id={$product->id}, brand={$brandId}, cat={$categoryId})");
            });

        } catch (Exception $e) {
            Log::error("❌ Global job error [{$this->name}]: " . $e->getMessage());
            throw $e;
        }
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

        Log::info("✨ Global: new brand '{$brandName}' id={$newBrand->id}");
        return $newBrand->id;
    }

    private function resolveCategory(?string $categoryName): int
    {
        if (empty($categoryName)) {
            return self::DEFAULT_CATEGORY;
        }

        $normalized = mb_strtolower(trim($categoryName));

        // ka translation title-ით ძებნა
        $category = ProductCategory::whereHas('translations',
            fn ($q) => $q->where('locale', 'ka')->whereRaw('LOWER(TRIM(title)) = ?', [$normalized])
        )->first();

        if ($category) {
            return $category->id;
        }

        Log::warning("⚠️ Global: category not mapped '{$categoryName}' → default");
        return self::DEFAULT_CATEGORY;
    }

    private function downloadImages(Product $product, array $images): void
    {
        $mainSet = false;
        $bulk    = [];

        foreach ($images as $index => $url) {
            if (empty($url)) {
                continue;
            }

            try {
                $resp = Http::timeout(30)->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'Referer'    => 'https://citrus.ge/',
                ])->get($url);

                if (!$resp->successful()) {
                    continue;
                }

                $ext      = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $filename = Str::random(40) . '.' . strtolower($ext);
                $path     = "uploads/products/{$product->id}/{$filename}";

                Storage::disk('public')->put($path, $resp->body());

                if (!$mainSet) {
                    $product->update(['main_image' => $path]);
                    $mainSet = true;
                } else {
                    $bulk[] = [
                        'product_id' => $product->id,
                        'path'       => $path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

            } catch (Exception $e) {
                Log::warning("⚠️ Global image download: " . $e->getMessage());
            }
        }

        if (!empty($bulk)) {
            ProductImage::insert($bulk);
        }
    }
}