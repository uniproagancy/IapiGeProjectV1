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

class MetromartProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    private const SUPPLIER_ID          = 12;
    private const DEFAULT_BRAND_ID     = 1;
    private const FALLBACK_CATEGORY_ID = 205;
    private const CACHE_DURATION_BRAND = 24 * 60;
    private const MAX_IMAGE_SIZE       = 5 * 1024 * 1024;
    private const SHORT_SPEC_LIMIT     = 5;
    private const BASE_URL             = 'https://metromart.ge';

    public function __construct(
        public string $model,
        public float $price = 0.0  // ← Excel-იდან გადმოცემული ფასი
    ) {}

    public function handle(): void
    {
        try {
            Log::info("🛒 Metromart: დაიწყო | model={$this->model} | excel_price={$this->price}");

            $productUrl = $this->searchProduct($this->model);
            if (!$productUrl) {
                Log::info("🛒 Metromart: ვერ მოიძებნა | model={$this->model}");
                return;
            }

            Log::info("✅ Metromart: URL მოიძებნა | model={$this->model} | url={$productUrl}");

            $html = $this->fetchPage($productUrl);
            if (!$html) {
                Log::warning("⚠️ Metromart: გვერდი ვერ ჩამოიტვირთა | url={$productUrl}");
                return;
            }

            $data = $this->parsePage($html, $productUrl);
            if (!$data || empty($data['name'])) {
                Log::warning("⚠️ Metromart: parse ვერ მოხდა | url={$productUrl}");
                return;
            }

            // Excel-ის ფასი override-ავს საიტის ფასს
            if ($this->price > 0) {
                $data['price']          = $this->price;
                $data['discount_price'] = null;
            }

            $this->saveProduct($data);

            Log::info("✅ Metromart: შენახულია | model={$this->model} | name={$data['name']} | price={$data['price']}");

        } catch (Exception $e) {
            Log::error("❌ MetromartProductJob შეცდომა | model={$this->model} | {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Search
    // ============================================

    private function searchProduct(string $model): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept'          => 'application/json',
                    'Accept-Language' => 'ka',
                    'Referer'         => self::BASE_URL . '/',
                ])
                ->get(self::BASE_URL . '/api/search', ['q' => $model, 'limit' => 5]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $products = $data['products'] ?? $data['data'] ?? $data ?? [];

            if (empty($products)) return null;

            $first = is_array($products[0]) ? $products[0] : null;
            if (!$first) return null;

            $slug = $first['slug'] ?? $first['url'] ?? null;
            if (!$slug) return null;

            return str_starts_with($slug, 'http') ? $slug : self::BASE_URL . '/' . ltrim($slug, '/');

        } catch (Exception $e) {
            Log::warning("⚠️ Metromart search error: {$e->getMessage()} | model={$model}");
            return null;
        }
    }

    // ============================================
    // Fetch Page
    // ============================================

    private function fetchPage(string $url): ?string
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING       => 'gzip, deflate',
            CURLOPT_HTTPHEADER     => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,*/*;q=0.9',
                'Accept-Language: ka,en;q=0.9',
                'Referer: https://metromart.ge/',
            ],
        ]);

        $body     = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error    = curl_error($curl);
        curl_close($curl);

        if ($error || $httpCode !== 200) {
            Log::warning("⚠️ Metromart fetchPage: HTTP={$httpCode} | url={$url}");
            return null;
        }

        return $body ?: null;
    }

    // ============================================
    // Parse
    // ============================================

    private function parsePage(string $html, string $url): ?array
    {
        // JSON-LD
        $json = null;
        if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m)) {
            $decoded = json_decode($m[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $json = $decoded;
            }
        }

        // სახელი
        $name = $json['name'] ?? null;
        if (!$name && preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $html, $m)) {
            $name = trim(strip_tags($m[1]));
        }
        if (!$name) return null;

        // SKU
        $sku = $json['sku'] ?? $json['mpn'] ?? null;

        // ბრენდი
        $brand = $json['brand']['name'] ?? null;

        // აღწერა
        $description = $json['description'] ?? null;

        // ფასი — საიტიდან (Excel-ის ფასი handle()-ში override-ავს)
        $price         = (float) ($json['offers']['price'] ?? 0);
        $discountPrice = null;
        if (!empty($json['offers'][0])) {
            $prices = collect($json['offers'])->pluck('price')->sort();
            $price  = (float) $prices->last();
            if ($prices->count() > 1) {
                $discountPrice = (float) $prices->first();
            }
        }

        // სურათები
        $images = [];
        if (!empty($json['image'])) {
            $images = is_array($json['image']) ? $json['image'] : [$json['image']];
        }
        if (empty($images) && preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $m)) {
            $images[] = $m[1];
        }
        $images = array_values(array_unique(array_filter($images)));

        // სპეციფიკაციები
        $fullSpecs  = $this->extractFullSpecs($html);
        $shortSpecs = array_slice(reset($fullSpecs) ?: [], 0, self::SHORT_SPEC_LIMIT, true);

        // მარაგი
        $inStock = 1;
        if (!empty($json['offers']['availability'])) {
            $inStock = str_contains($json['offers']['availability'], 'InStock') ? 1 : 0;
        }

        return [
            'name'          => $name,
            'sku'           => $sku ? 'METRO-' . $sku : 'METRO-' . Str::slug($name),
            'brand'         => $brand,
            'description'   => $description,
            'images'        => $images,
            'fullSpecs'     => $fullSpecs,
            'shortSpecs'    => $shortSpecs,
            'in_stock'      => $inStock,
            'price'         => $price,
            'discount_price'=> $discountPrice,
            'url'           => $url,
        ];
    }

    // ============================================
    // Extract Specs
    // ============================================

    private function extractFullSpecs(string $html): array
    {
        $specs        = [];
        $currentGroup = 'მახასიათებლები';

        if (preg_match('/<table[^>]*>(.*?)<\/table>/s', $html, $tableMatch)) {
            if (preg_match_all('/<tr[^>]*>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<\/tr>/s', $tableMatch[1], $rows, PREG_SET_ORDER)) {
                foreach ($rows as $row) {
                    $key   = trim(strip_tags($row[1]));
                    $value = trim(strip_tags($row[2]));
                    if ($key && $value) $specs[$currentGroup][$key] = $value;
                }
            }
        }

        if (empty($specs) && preg_match_all('/<dt[^>]*>(.*?)<\/dt>\s*<dd[^>]*>(.*?)<\/dd>/s', $html, $rows, PREG_SET_ORDER)) {
            foreach ($rows as $row) {
                $key   = trim(strip_tags($row[1]));
                $value = trim(strip_tags($row[2]));
                if ($key && $value) $specs[$currentGroup][$key] = $value;
            }
        }

        return $specs;
    }

    // ============================================
    // Save
    // ============================================

    private function saveProduct(array $data): void
    {
        $sku      = $data['sku'];
        $existing = Product::where('sku', $sku)->first();
        $isNew    = !$existing;
        $brandId  = $this->getBrandId($data['brand']);
        $inStock  = $data['in_stock'];
        $quantity = $inStock ? 5 : 0;

        DB::transaction(function () use ($data, $sku, &$existing, $isNew, $brandId, $inStock, $quantity) {

            if ($isNew) {
                $existing = Product::create([
                    'sku'           => $sku,
                    'supplier_id'   => self::SUPPLIER_ID,
                    'brand_id'      => $brandId,
                    'category_id'   => self::FALLBACK_CATEGORY_ID,
                    'quantity'      => $quantity,
                    'in_stock'      => $inStock,
                    'show'          => $inStock,
                    'active'        => 1,
                    'main_image'    => null,
                    'update_lock'   => 0,
                    'taxonomy_lock' => 0,
                ]);
                Log::info("➕ Metromart: ახალი | sku={$sku} | id={$existing->id}");
            } else {
                if ($existing->update_lock) {
                    Log::info("🔒 Metromart: ჩაკეტილია | sku={$sku}");
                    return;
                }
                $existing->update([
                    'quantity' => $quantity,
                    'in_stock' => $inStock,
                    'show'     => $inStock,
                    'active'   => 1,
                ]);
                Log::info("🔄 Metromart: განახლდა | sku={$sku} | id={$existing->id}");
            }

            // Translations
            foreach (['ka', 'en', 'ru'] as $locale) {
                ProductTranslation::updateOrCreate(
                    ['product_id' => $existing->id, 'locale' => $locale],
                    [
                        'title'       => $data['name'],
                        'slug'        => Str::slug($data['name']) . '-' . $existing->id,
                        'description' => $locale === 'ka' ? ($data['description'] ?? null) : null,
                        'keywords'    => null,
                    ]
                );
            }

            // Price — Excel-ის ფასი პრიორიტეტულია
            ProductPrice::updateOrCreate(
                ['product_id' => $existing->id],
                [
                    'dealer_price'     => $data['price'],
                    'regular_price'    => $data['price'],
                    'discount_price'   => $data['discount_price'],
                    'discount_percent' => $data['discount_price'] && $data['price'] > 0
                        ? (int) round((($data['price'] - $data['discount_price']) / $data['price']) * 100)
                        : 0,
                ]
            );

            // Short Specs
            ProductShortSpecification::where('product_id', $existing->id)->forceDelete();
            $rows = [];
            foreach ($data['shortSpecs'] as $name => $value) {
                $rows[] = [
                    'product_id' => $existing->id,
                    'name'       => $name,
                    'value'      => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($rows) ProductShortSpecification::insert($rows);

            // Full Specs
            $sectionIds = ProductFullSpecificationSection::where('product_id', $existing->id)->pluck('id');
            ProductFullSpecificationItem::whereIn('section_id', $sectionIds)->forceDelete();
            ProductFullSpecificationSection::where('product_id', $existing->id)->forceDelete();

            foreach ($data['fullSpecs'] as $groupName => $specs) {
                if (empty($specs)) continue;
                $section = ProductFullSpecificationSection::create([
                    'product_id' => $existing->id,
                    'name'       => $groupName,
                ]);
                foreach ($specs as $specName => $specValue) {
                    ProductFullSpecificationItem::create([
                        'section_id' => $section->id,
                        'name'       => $specName,
                        'value'      => $specValue,
                        'filter'     => 0,
                    ]);
                }
            }

            // Images
            if (!empty($data['images'])) {
                $this->saveImages($existing, $data['images'], $isNew);
            }
        });
    }

    // ============================================
    // Images
    // ============================================

    private function saveImages(Product $product, array $images, bool $isNew): void
    {
        if (!$isNew) {
            $oldImages = ProductImage::where('product_id', $product->id)->withTrashed()->get();
            foreach ($oldImages as $old) {
                if (!empty($old->path) && Storage::disk('public')->exists($old->path)) {
                    Storage::disk('public')->delete($old->path);
                }
            }
            ProductImage::where('product_id', $product->id)->forceDelete();
        }

        $processedUrls = [];
        $mainSet       = false;
        $gallery       = [];

        foreach ($images as $imageUrl) {
            if (empty($imageUrl) || in_array($imageUrl, $processedUrls)) continue;
            $processedUrls[] = $imageUrl;

            try {
                $response = Http::timeout(30)->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Referer'    => self::BASE_URL . '/',
                ])->get($imageUrl);

                if (!$response->successful()) continue;
                if (strlen($response->body()) === 0 || strlen($response->body()) > self::MAX_IMAGE_SIZE) continue;

                $ext  = $this->getImageExtension($imageUrl);
                $path = "uploads/products/{$product->id}/" . Str::random(40) . ".{$ext}";
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
            } catch (Exception $e) {
                Log::warning("⚠️ Metromart: სურათი ვერ ჩამოიტვირთა | {$imageUrl}");
            }
        }

        if ($gallery) ProductImage::insert($gallery);
    }

    // ============================================
    // Brand
    // ============================================

    private function getBrandId(?string $brandName): int
    {
        if (empty($brandName)) return self::DEFAULT_BRAND_ID;

        $normalized = mb_strtolower(trim($brandName));

        return Cache::remember(
            'metromart_brand_' . md5($normalized),
            now()->addMinutes(self::CACHE_DURATION_BRAND),
            function () use ($brandName, $normalized) {
                $brand = ProductBrand::whereHas('translations',
                    fn($q) => $q->whereRaw('LOWER(TRIM(title)) = ?', [$normalized])
                )->first();

                if ($brand) return $brand->id;

                $new = ProductBrand::create(['active' => 1, 'show' => 1]);
                foreach (['ka', 'en'] as $locale) {
                    ProductBrandTranslation::create([
                        'product_brand_id' => $new->id,
                        'locale'           => $locale,
                        'title'            => $brandName,
                        'slug'             => Str::slug($brandName) . '-' . $new->id . ($locale === 'en' ? '-en' : ''),
                    ]);
                }

                Log::info("✨ Metromart: ახალი ბრენდი '{$brandName}' id={$new->id}");
                return $new->id;
            }
        );
    }

    // ============================================
    // Helpers
    // ============================================

    private function getImageExtension(string $url): string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']) ? $ext : 'jpg';
    }

    public function failed(Exception $exception): void
    {
        Log::error("🚨 MetromartProductJob permanently failed | model={$this->model} | {$exception->getMessage()}");
    }
}