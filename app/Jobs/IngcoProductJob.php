<?php

namespace App\Jobs;

use App\Models\IngcoProduct;
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

class IngcoProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    private const SUPPLIER_ID          = 16;
    private const DEFAULT_BRAND_ID     = 1;
    private const FALLBACK_CATEGORY_ID = 205;
    private const CACHE_DURATION_BRAND = 24 * 60;
    private const MAX_IMAGE_SIZE       = 5 * 1024 * 1024;
    private const SHORT_SPEC_LIMIT     = 5;
    private const BASE_URL             = 'https://ingco.ge';
    private const SEARCH_URL           = 'https://ingco.ge/ka/catalog/searchtermautocomplete';

    public function __construct(public string $model) {}

    public function handle(): void
    {
        try {
            Log::info("🔧 Ingco: დაიწყო | model={$this->model}");

            // Step 1: Search
            $productUrl = $this->searchProduct($this->model);
            if (!$productUrl) {
                Log::info("🔧 Ingco: ვერ მოიძებნა | model={$this->model}");
                return;
            }

            Log::info("✅ Ingco: URL მოიძებნა | model={$this->model} | url={$productUrl}");

            // Step 2: Fetch product page
            $html = $this->fetchPage($productUrl);
            if (!$html) {
                Log::warning("⚠️ Ingco: გვერდი ვერ ჩამოიტვირთა | url={$productUrl}");
                return;
            }

            // Step 3: Parse
            $data = $this->parsePage($html, $productUrl);
            if (!$data || empty($data['name'])) {
                Log::warning("⚠️ Ingco: parse ვერ მოხდა | url={$productUrl}");
                return;
            }

            // Step 4: ფასი IngcoProduct-იდან
            $ingcoProduct = IngcoProduct::where('sku', $this->model)->first();
            if (!$ingcoProduct) {
                Log::info("⏭️ Ingco: SKU არ არის ბაზაში | sku={$this->model}");
                return;
            }

            $data['price']          = (float) $ingcoProduct->price;
            $data['discount_price'] = $ingcoProduct->discount_price ? (float) $ingcoProduct->discount_price : null;
            $data['stock']          = (int) ($ingcoProduct->stock ?? 1);

            // Step 5: Save
            $this->saveProduct($data);

            Log::info("✅ Ingco: შენახულია | model={$this->model} | name={$data['name']}");

        } catch (Exception $e) {
            Log::error("❌ IngcoProductJob შეცდომა | model={$this->model} | {$e->getMessage()}");
            throw $e;
        }
    }

    // ============================================
    // Step 1 — Search
    // ============================================

    private function searchProduct(string $model): ?string
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept'          => '*/*',
                'Accept-Language' => 'ka',
                'Referer'         => self::BASE_URL . '/',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get(self::SEARCH_URL, [
                'term'      => $model,
                'pageIndex' => 0,
            ]);

        if (!$response->successful()) {
            Log::warning("⚠️ Ingco search: HTTP={$response->status()} | model={$model}");
            return null;
        }

        $html = $response->body();

        Log::info("🔍 Ingco search response: " . substr($html, 0, 200) . " | model={$model}");

        // search__result__item class-ით
        if (preg_match('/<a[^>]+href="(\/ka\/[^"]+)"[^>]*class="search__result__item/', $html, $m)) {
            return self::BASE_URL . $m[1];
        }

        // class ბოლოში
        if (preg_match('/class="search__result__item[^"]*"[^>]*href="(\/ka\/[^"]+)"/', $html, $m)) {
            return self::BASE_URL . $m[1];
        }

        // fallback — ნებისმიერი /ka/ product link
        if (preg_match_all('/<a[^>]+href="(\/ka\/[^"]+)"/', $html, $matches)) {
            foreach ($matches[1] as $href) {
                // product link-ები შეიცავს ingco-ს ან ხელსაწყოს სახელს
                if (str_contains($href, 'ingco') || preg_match('/\/ka\/[a-z0-9\-]+-p\d+/', $href)) {
                    return self::BASE_URL . $href;
                }
            }
            // პირველი /ka/ link
            if (!empty($matches[1][0])) {
                return self::BASE_URL . $matches[1][0];
            }
        }

        return null;
    }

    // ============================================
    // Step 2 — Fetch Page
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
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: ka,en;q=0.9',
                'Referer: https://ingco.ge/',
            ],
        ]);

        $body     = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error    = curl_error($curl);
        curl_close($curl);

        if ($error) {
            Log::warning("⚠️ Ingco fetchPage curl error: {$error} | url={$url}");
            return null;
        }

        if ($httpCode !== 200) {
            Log::warning("⚠️ Ingco fetchPage: HTTP={$httpCode} | url={$url}");
            return null;
        }

        Log::info("✅ Ingco fetchPage: OK | url={$url}");
        return $body ?: null;
    }

    // ============================================
    // Step 3 — Parse
    // ============================================

    private function parsePage(string $html, string $url): ?array
    {
        // JSON-LD — მთავარი წყარო
        $json = null;
        if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m)) {
            $decoded = json_decode($m[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $json = $decoded;
            }
        }

        // სახელი
        $name = null;
        if (!empty($json['name'])) {
            $name = trim($json['name']);
        }
        if (!$name && preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $html, $m)) {
            $name = trim(strip_tags($m[1]));
        }
        if (!$name) return null;

        // SKU
        $sku = $json['sku'] ?? $json['mpn'] ?? null;
        if (!$sku) {
            if (preg_match('/sku["\s:]+([A-Z0-9\-]+)/i', $html, $m)) {
                $sku = trim($m[1]);
            }
        }

        // ბრენდი
        $brand = $json['brand']['name'] ?? null;
        if (!$brand && preg_match('/brand["\s:]+["\'](.*?)["\']/i', $html, $m)) {
            $brand = trim($m[1]);
        }
        if (!$brand) $brand = 'INGCO';

        // აღწერა
        $description = $json['description'] ?? null;
        if (!$description && preg_match('/<div[^>]+class="[^"]*product-description[^"]*"[^>]*>(.*?)<\/div>/s', $html, $m)) {
            $description = trim(strip_tags($m[1]));
        }

        // სურათები
        $images = [];
        if (!empty($json['image'])) {
            if (is_array($json['image'])) {
                foreach ($json['image'] as $img) {
                    $images[] = is_string($img) ? $img : ($img['url'] ?? '');
                }
            } else {
                $images[] = $json['image'];
            }
        }

        // fallback სურათები OG-დან
        if (empty($images)) {
            if (preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $m)) {
                $images[] = $m[1];
            }
        }

        // gallery სურათები
        if (preg_match_all('/<img[^>]+src="([^"]+\/images\/thumbs\/[^"]+)"/i', $html, $m)) {
            foreach ($m[1] as $img) {
                if (!in_array($img, $images)) {
                    $images[] = strpos($img, 'http') === 0 ? $img : self::BASE_URL . $img;
                }
            }
        }

        $images = array_values(array_unique(array_filter($images)));

        // სპეციფიკაციები — ცხრილიდან
        $fullSpecs  = $this->extractFullSpecs($html);
        $shortSpecs = array_slice($fullSpecs['მახასიათებლები'] ?? (reset($fullSpecs) ?: []), 0, self::SHORT_SPEC_LIMIT, true);

        // მარაგი — JSON-LD-დან
        $inStock = 1;
        if (!empty($json['offers']['availability'])) {
            $inStock = str_contains($json['offers']['availability'], 'InStock') ? 1 : 0;
        }

        return [
            'name'        => $name,
            'sku'         => $sku ? 'INGCO-' . $sku : 'INGCO-' . Str::slug($name),
            'brand'       => $brand,
            'description' => $description,
            'images'      => $images,
            'fullSpecs'   => $fullSpecs,
            'shortSpecs'  => $shortSpecs,
            'in_stock'    => $inStock,
            'url'         => $url,
        ];
    }

    // ============================================
    // Extract Specs
    // ============================================

    private function extractFullSpecs(string $html): array
    {
        $specs        = [];
        $currentGroup = 'მახასიათებლები';

        // ცხრილი — <tr><td>name</td><td>value</td></tr>
        if (preg_match('/<table[^>]*>(.*?)<\/table>/s', $html, $tableMatch)) {
            if (preg_match_all('/<tr[^>]*>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<\/tr>/s', $tableMatch[1], $rows, PREG_SET_ORDER)) {
                foreach ($rows as $row) {
                    $key   = trim(strip_tags($row[1]));
                    $value = trim(strip_tags($row[2]));
                    if ($key && $value) {
                        $specs[$currentGroup][$key] = $value;
                    }
                }
            }
        }

        // dl/dt/dd სია
        if (empty($specs) && preg_match_all('/<dt[^>]*>(.*?)<\/dt>\s*<dd[^>]*>(.*?)<\/dd>/s', $html, $rows, PREG_SET_ORDER)) {
            foreach ($rows as $row) {
                $key   = trim(strip_tags($row[1]));
                $value = trim(strip_tags($row[2]));
                if ($key && $value) {
                    $specs[$currentGroup][$key] = $value;
                }
            }
        }

        return $specs;
    }

    // ============================================
    // Step 5 — Save
    // ============================================

    private function saveProduct(array $data): void
    {
        $sku      = $data['sku'];
        $existing = Product::where('sku', $sku)->first();
        $isNew    = !$existing;

        $brandId  = $this->getBrandId($data['brand']);
        $inStock  = $data['in_stock'];
        $quantity = $inStock ? max((int)($data['stock'] ?? 1), 1) : 0;

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

                Log::info("➕ Ingco: ახალი პროდუქტი | sku={$sku} | id={$existing->id}");
            } else {
                if ($existing->update_lock) {
                    Log::info("🔒 Ingco: ჩაკეტილია | sku={$sku}");
                    return;
                }

                $existing->update([
                    'quantity' => $quantity,
                    'in_stock' => $inStock,
                    'show'     => $inStock,
                    'active'   => 1,
                ]);

                Log::info("🔄 Ingco: განახლდა | sku={$sku} | id={$existing->id}");
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

            // Price — IngcoProduct-იდან
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
                if (strlen($response->body()) > self::MAX_IMAGE_SIZE) continue;
                if (strlen($response->body()) === 0) continue;

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
                Log::warning("⚠️ Ingco: სურათი ვერ ჩამოიტვირთა | {$imageUrl} | {$e->getMessage()}");
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
            'ingco_brand_' . md5($normalized),
            now()->addMinutes(self::CACHE_DURATION_BRAND),
            function () use ($brandName, $normalized) {
                $brand = ProductBrand::whereHas('translations',
                    fn($q) => $q->whereRaw('LOWER(TRIM(title)) = ?', [$normalized])
                )->first();

                if ($brand) {
                    Log::info("✅ Ingco brand found: '{$brandName}' → id={$brand->id}");
                    return $brand->id;
                }

                $new = ProductBrand::create(['active' => 1, 'show' => 1]);

                foreach (['ka', 'en'] as $locale) {
                    ProductBrandTranslation::create([
                        'product_brand_id' => $new->id,
                        'locale'           => $locale,
                        'title'            => $brandName,
                        'slug'             => Str::slug($brandName) . '-' . $new->id . ($locale === 'en' ? '-en' : ''),
                    ]);
                }

                Log::info("✨ Ingco: ახალი ბრენდი '{$brandName}' id={$new->id}");
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
        Log::error("🚨 IngcoProductJob permanently failed | model={$this->model} | {$exception->getMessage()}");
    }
}