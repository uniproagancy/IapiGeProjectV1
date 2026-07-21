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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;

class ThermocenterProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 3;

    private const SUPPLIER_ID = 15;

    protected string $productUrl;

    public function __construct(string $productUrl)
    {
        $this->productUrl = $productUrl;
    }

    public function handle(): void
    {
        try {
            Log::info("🌡️ ThermocenterJob: {$this->productUrl}");

            $html = $this->fetchUrl($this->productUrl);
            if (!$html) {
                Log::warning("⚠️ ThermocenterJob: HTML ვერ ჩაიტვირთა | {$this->productUrl}");
                return;
            }

            $data = $this->parseProduct($html);
            if (!$data) {
                Log::warning("⚠️ ThermocenterJob: parse ვერ მოხდა | {$this->productUrl}");
                return;
            }

            $this->saveProduct($data);

        } catch (Exception $e) {
            Log::error("❌ ThermocenterJob error: {$e->getMessage()} | {$this->productUrl}");
            throw $e;
        }
    }

    private function parseProduct(string $html): ?array
    {
        // ==========================================
        // JSON-LD (Schema.org) — მთავარი წყარო
        // ==========================================
        $json = null;
        if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m)) {
            $json = json_decode($m[1], true);
        }

        if (!$json || json_last_error() !== JSON_ERROR_NONE) {
            Log::warning("⚠️ Thermocenter: JSON-LD ვერ დაიპარსა | {$this->productUrl}");
            return null;
        }

        // სათაური
        $title = $json['name'] ?? null;
        if (!$title) return null;

        // SKU
        $sku = $json['sku'] ?? null;

        // ბრენდი
        $brand = $json['brand']['name'] ?? null;

        // აღწერა
        $description = $json['description'] ?? null;

        // სურათები
        $images = [];
        if (!empty($json['image'])) {
            $images = is_array($json['image']) ? $json['image'] : [$json['image']];
        }

        // ფასი და მარაგი offers-იდან
        $price    = null;
        $inStock  = 0;
        $offer    = $json['offers'][0] ?? $json['offers'] ?? null;
        if ($offer) {
            $price   = (float) ($offer['price'] ?? 0);
            $inStock = str_contains($offer['availability'] ?? '', 'InStock') ? 1 : 0;
        }

        // ძველი ფასი — HTML-დან (JSON-LD-ში არ არის)
        $oldPrice = null;
        if (preg_match('/<span[^>]+id="sec_list_price_\d+"[^>]*>(.*?)<\/span>/s', $html, $m)) {
            $raw = strip_tags($m[1]);
            $raw = trim(str_replace([',', ' '], ['', ''], $raw));
            $num = preg_replace('/[^\d.]/', '', $raw);
            if ($num && (float)$num !== $price) {
                $oldPrice = (float) $num;
            }
        }

        // კატეგორია — breadcrumb-იდან
        $category = null;
        if (preg_match_all('/<a[^>]+class="ty-breadcrumbs__a"[^>]*><bdi>([^<]+)<\/bdi><\/a>/u', $html, $m)) {
            $crumbs   = $m[1];
            $category = end($crumbs);
        }

        // CS-Cart product_id
        $externalId = null;
        if (preg_match('/product_id=(\d+)/', $html, $m)) {
            $externalId = $m[1];
        }

        return [
            'title'       => $title,
            'sku'         => $sku ? 'THERMO-' . $sku : 'THERMO-' . md5($this->productUrl),
            'price'       => $price ?? 0,
            'old_price'   => $oldPrice,
            'in_stock'    => $inStock,
            'images'      => array_values($images),
            'description' => $description,
            'brand'       => $brand,
            'category'    => $category,
            'url'         => $this->productUrl,
            'external_id' => $externalId,
        ];
    }

    private function saveProduct(array $data): void
    {
        $finalPrice = $data['old_price'] ?? $data['price'];

        if ($finalPrice < 100) {
            Log::info("⏭️ Thermocenter: ფასი 100₾-ზე ნაკლებია ({$finalPrice}₾), გამოტოვება | {$data['sku']}");
            return;
        }

        DB::transaction(function () use ($data) {
            $brandId    = $this->getBrandId($data['brand']);
            $categoryId = $this->getCategoryId($data['category']);

            $existing = Product::where('sku', $data['sku'])->first();

            if ($existing) {
                // განახლება
                $existing->update([
                    'brand_id'    => $brandId,
                    'category_id' => $categoryId,
                    'in_stock'    => $data['in_stock'],
                    'quantity'    => $data['in_stock'] ? 1 : 0,
                    'show'        => $data['in_stock'],
                    'active'      => $data['in_stock'],
                ]);

                $markup = 1.2;
                ProductPrice::updateOrCreate(
                    ['product_id' => $existing->id],
                    [
                        'regular_price'  => round(($data['old_price'] ?? $data['price']) * $markup, 2),
                        'discount_price' => $data['old_price'] ? round($data['price'] * $markup, 2) : null,
                        'dealer_price'   => round($data['price'] * $markup, 2),
                    ]
                );
                if (!empty($data['description'])) {
                    ProductTranslation::where('product_id', $existing->id)
                        ->where('locale', 'ka')
                        ->update(['description' => $data['description']]);
                }

                if (!empty($data['images'])) {
                    $this->updateImages($existing, $data['images']);
                }

                Log::info("🔁 Thermocenter: განახლდა | sku={$data['sku']} | id={$existing->id}");
                return;
            }

            // ახალი პროდუქტი
            $product = Product::create([
                'supplier_id'         => self::SUPPLIER_ID,
                'supplier_product_id' => $data['external_id'],
                'brand_id'            => $brandId,
                'category_id'         => $categoryId,
                'sku'                 => $data['sku'],
                'in_stock'            => $data['in_stock'],
                'quantity'            => $data['in_stock'] ? 1 : 0,
                'show'                => $data['in_stock'],
                'active'              => 1,
                'main_image'          => null,
            ]);

            ProductPrice::create([
                'product_id'     => $product->id,
                'regular_price'  => $data['old_price'] ?? $data['price'],
                'discount_price' => $data['old_price'] ? $data['price'] : null,
                'dealer_price'   => $data['price'],
            ]);

            $slug = Str::slug($data['title']) . '-' . $product->id;
            foreach (['ka', 'en', 'ru'] as $locale) {
                ProductTranslation::create([
                    'product_id'  => $product->id,
                    'locale'      => $locale,
                    'title'       => $data['title'],
                    'slug'        => $slug,
                    'description' => $locale === 'ka' ? $data['description'] : null,
                ]);
            }

            if (!empty($data['images'])) {
                $this->updateImages($product, $data['images']);
            }

            Log::info("✨ Thermocenter: ახალი პროდუქტი | sku={$data['sku']} | id={$product->id} | name={$data['title']}");
        });
    }

    private function updateImages(Product $product, array $imageUrls): void
    {
        $mainSet = false;
        $gallery = [];

        foreach ($imageUrls as $index => $url) {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0', 'Referer' => 'https://thermocenter.ge/'])
                ->get($url);

            if (!$response->successful()) continue;

            $ext      = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'webp');
            $ext      = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) ? $ext : 'jpg';
            $filename = Str::random(40) . '.' . $ext;
            $path     = "uploads/products/{$product->id}/{$filename}";

            Storage::disk('public')->put($path, $response->body());

            if (!$mainSet) {
                $product->update(['main_image' => $path]);
                $mainSet = true;
            } else {
                $gallery[] = [
                    'product_id' => $product->id,
                    'path'       => $path,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Log::info("📸 Thermocenter: სურათი შენახულია | {$filename}");
        }

        if (!empty($gallery)) {
            // ძველი gallery-ს წაშლა
            ProductImage::where('product_id', $product->id)->forceDelete();
            ProductImage::insert($gallery);
        }
    }

    private function getBrandId(?string $brandName): int
    {
        if (!$brandName) return 6;

        $normalized = mb_strtolower(trim($brandName));

        $brand = ProductBrand::whereHas('translations',
            fn($q) => $q->whereRaw('LOWER(TRIM(title)) = ?', [$normalized])
        )->first();

        if ($brand) return $brand->id;

        $newBrand = ProductBrand::create(['active' => 1, 'show' => 1]);

        foreach (['ka', 'en'] as $locale) {
            ProductBrandTranslation::create([
                'product_brand_id' => $newBrand->id,
                'locale'           => $locale,
                'title'            => $brandName,
                'slug'             => Str::slug($brandName) . '-' . $newBrand->id . ($locale === 'en' ? '-en' : ''),
            ]);
        }

        Log::info("✨ Thermocenter: ახალი ბრენდი '{$brandName}' id={$newBrand->id}");
        return $newBrand->id;
    }

    private function getCategoryId(?string $categoryName): int
    {
        if (!$categoryName) return 3;

        $cat = ProductCategory::whereHas('translations',
            fn($q) => $q->whereRaw('LOWER(TRIM(title)) = ?', [mb_strtolower(trim($categoryName))])
        )->first();

        if ($cat) {
            Log::info("✅ Thermocenter category mapped: '{$categoryName}' → id={$cat->id}");
            return $cat->id;
        }

        Log::warning("⚠️ Thermocenter category not mapped: '{$categoryName}'");
        return 3;
    }

    private function fetchUrl(string $url): ?string
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => 'gzip, deflate',
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: ka,en;q=0.9',
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error    = curl_error($curl);
        curl_close($curl);

        if ($error || $httpCode !== 200) {
            Log::warning("⚠️ Thermocenter fetch: HTTP={$httpCode} | {$url}");
            return null;
        }

        return $response;
    }
}