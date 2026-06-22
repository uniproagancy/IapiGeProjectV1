<?php

namespace App\Jobs;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductBrandTranslation;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductTranslation;
use App\Models\Product\GlobalNotFound;
use App\Services\Products\MideaService;
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

class MideaProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;
    public int $backoff = 30;

    private const SUPPLIER_ID      = 6;
    private const SKU_PREFIX       = 'MIDEA-';
    private const DEFAULT_CATEGORY = 6;
    private const DEFAULT_BRAND    = 6;

    public function __construct(
        public string $name,
        public int    $stock = 0,
    ) {}

    public function handle(): void
    {
        Log::info("🔄 Midea job START: '{$this->name}' (stock={$this->stock})");

        try {
            if ($this->stock <= 0) {
                $this->disableByName();
                return;
            }

            $data = (new MideaService())->searchByName($this->name);

            if (!$data || empty($data['sku'])) {
                Log::warning("⚠️ Midea job: ვერ მოიძებნა — {$this->name}");

                GlobalNotFound::updateOrCreate(
                    ['name' => $this->name],
                    [
                        'stock'  => $this->stock,
                        'price'  => null,
                        'reason' => 'midea_no_result',
                    ]
                );

                return;
            }

            $sku      = self::SKU_PREFIX . $data['sku'];
            $existing = Product::where('sku', $sku)->first();

            if ($existing && $existing->update_lock) {
                Log::info("🔒 Midea job: locked, skip — {$sku}");
                return;
            }

            DB::transaction(function () use ($data, $sku, $existing) {
                $brandId = $this->resolveBrand($data['brand'] ?? 'Midea');

                if ($existing) {
                    $product    = $existing;
                    $updateData = [
                        'quantity' => $this->stock,
                        'in_stock' => 1,
                        'show'     => 1,
                        'active'   => 1,
                    ];

                    if (!$existing->taxonomy_lock) {
                        $updateData['brand_id'] = $brandId;
                    } else {
                        Log::info("🏷️ Midea: taxonomy locked, brand უცვლელი — {$sku}");
                    }

                    $product->update($updateData);
                    Log::info("🔁 Midea: updated {$sku} (stock={$this->stock})");
                } else {
                    $product = Product::create([
                        'supplier_product_id' => null,
                        'brand_id'            => $brandId,
                        'category_id'         => self::DEFAULT_CATEGORY,
                        'sku'                 => $sku,
                        'supplier_id'         => self::SUPPLIER_ID,
                        'main_image'          => null,
                        'active'              => 1,
                        'quantity'            => $this->stock,
                        'in_stock'            => 1,
                        'show'                => 1,
                    ]);
                    Log::info("✨ Midea: created {$sku} (id={$product->id}, stock={$this->stock})");
                }

                // ===== ფასი JSON-დან =====
                $regularPrice  = (float) ($data['price'] ?? 0);
                $salePrice     = !empty($data['sale_price']) ? (float) $data['sale_price'] : null;

                // sale_price მხოლოდ თუ ნამდვილად დაბალია
                $discountPrice = ($salePrice && $salePrice < $regularPrice) ? $salePrice : null;

                $discountPercent = 0;
                if ($discountPrice && $regularPrice > 0) {
                    $discountPercent = (int) round((($regularPrice - $discountPrice) / $regularPrice) * 100);
                }

                ProductPrice::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'dealer_price'     => $regularPrice,
                        'regular_price'    => $regularPrice,
                        'discount_price'   => $discountPrice,
                        'discount_percent' => $discountPercent,
                    ]
                );

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

                if (!empty($data['images']) && empty($product->main_image)) {
                    $this->downloadImages($product, $data['images']);
                }

                GlobalNotFound::where('name', $this->name)->delete();

                Log::info("✅ Midea saved: {$sku} (regular={$regularPrice}, discount={$discountPrice})");
            });

        } catch (Exception $e) {
            Log::error("❌ Midea job error [{$this->name}]: " . $e->getMessage());
            throw $e;
        }
    }

    private function disableByName(): void
    {
        $product = Product::whereHas('translations',
            fn ($q) => $q->where('locale', 'ka')
                ->whereRaw('LOWER(TRIM(title)) = ?', [mb_strtolower(trim($this->name))])
        )->where('supplier_id', self::SUPPLIER_ID)->first();

        if (!$product) {
            Log::info("⏭️ Midea disable: '{$this->name}' ვერ მოიძებნა — გამოტოვება");
            return;
        }

        if ($product->update_lock) {
            Log::info("🔒 Midea disable: locked, skip — {$product->sku}");
            return;
        }

        $product->update([
            'quantity' => 0,
            'in_stock' => 0,
            'show'     => 0,
        ]);

        Log::info("🚫 Midea: disabled {$product->sku} (id={$product->id}) — stock=0");
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
        foreach (['ka', 'en'] as $locale) {
            ProductBrandTranslation::create([
                'product_brand_id' => $newBrand->id,
                'locale'           => $locale,
                'title'            => $brandName,
                'slug'             => Str::slug($brandName) . '-' . $newBrand->id . ($locale === 'en' ? '-en' : ''),
            ]);
        }

        Log::info("✨ Midea: new brand '{$brandName}' id={$newBrand->id}");
        return $newBrand->id;
    }

    private function downloadImages(Product $product, array $images): void
    {
        $mainSet = false;
        $bulk    = [];

        foreach ($images as $url) {
            if (empty($url)) continue;

            try {
                $resp = Http::timeout(30)->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'Referer'    => 'https://www.midea.ge/',
                ])->get($url);

                if (!$resp->successful()) continue;

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
                Log::warning("⚠️ Midea image: " . $e->getMessage());
            }
        }

        if (!empty($bulk)) {
            ProductImage::insert($bulk);
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error("🚨 MideaProductJob permanently failed", [
            'name'  => $this->name,
            'error' => $exception->getMessage(),
        ]);
    }
}