<?php

namespace App\Jobs;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductBrandTranslation;
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
use DOMDocument;
use DOMXPath;
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
    private const SEARCH_URL           = 'https://metromart.ge/find-products-suggestions';
    private const PRODUCT_URL          = 'https://metromart.ge/ka_GE/shop/product/';

    // $price — Excel-იდან გადმოცემული ფასი (თუ > 0, override-ავს საიტის ფასს)
    public function __construct(public string $model, public ?float $price = null) {}

    public function handle(): void
    {
        try {
            Log::info("🔍 Metromart: დაიწყო", ['model' => $this->model, 'excel_price' => $this->price]);

            // ===== Step 1: Search =====
            $productUrl = $this->searchProduct($this->model);
            if (!$productUrl) {
                Log::info("🔍 Metromart: შედეგი არ მოიძებნა", ['model' => $this->model]);
                return;
            }

            Log::info("✅ Metromart: URL მოიძებნა", ['model' => $this->model, 'url' => $productUrl]);

            // ===== Step 2: Fetch Page =====
            $html = $this->fetchPage($productUrl);
            if (!$html) {
                Log::warning("⚠️ Metromart: გვერდი ვერ ჩამოიტვირთა", ['url' => $productUrl]);
                return;
            }

            // ===== Step 3: Parse =====
            $data = $this->parsePage($html, $productUrl);
            if (!$data || empty($data['name'])) {
                Log::warning("⚠️ Metromart: პარსინგი ვერ მოხდა", ['url' => $productUrl]);
                return;
            }

            // ===== Step 4: Import =====
            $this->importProduct($data);

            Log::info("✅ Metromart: დასრულდა", ['model' => $this->model, 'name' => $data['name']]);

        } catch (Exception $e) {
            Log::error("❌ MetromartProductJob შეცდომა", [
                'model' => $this->model,
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
            ]);
            throw $e;
        }
    }

    // ============================================
    // Step 1 — Search
    // ============================================

    private function searchProduct(string $model): ?string
    {
        $model = trim(preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{200C}\x{200D}]/u', ' ', $model));
        $model = preg_replace('/\s+/u', ' ', $model);
        $model = trim($model);

        $cookieJar = new \GuzzleHttp\Cookie\CookieJar();

        // 1. მთავარი გვერდიდან csrf_token + session cookie ავიღოთ
        try {
            $initResponse = Http::withOptions(['cookies' => $cookieJar])
                ->timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',
                ])
                ->get(self::BASE_URL . '/ka_GE/');
        } catch (Exception $e) {
            Log::warning("⚠️ Metromart: session init ვერ მოხდა | {$e->getMessage()}");
            return null;
        }

        $csrfToken = null;
        if (preg_match('/csrf_token:\s*"([^"]+)"/', $initResponse->body(), $m)) {
            $csrfToken = $m[1];
        }

        if (!$csrfToken) {
            Log::warning("⚠️ Metromart: csrf_token ვერ მოიძებნა | model={$model}");
            return null;
        }

        // 2. POST request csrf_token-ით
        $response = Http::withOptions(['cookies' => $cookieJar])
            ->timeout(30)
            ->withHeaders([
                'Content-Type'     => 'application/json',
                'Accept'           => 'application/json, text/javascript, */*; q=0.01',
                'X-Requested-With' => 'XMLHttpRequest',
                'User-Agent'       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',
                'Referer'          => self::BASE_URL . '/ka_GE/',
                'Origin'           => self::BASE_URL,
            ])
            ->post(self::SEARCH_URL, [
                'jsonrpc' => '2.0',
                'method'  => 'call',
                'params'  => [
                    'search'     => $model,
                    'csrf_token' => $csrfToken,
                ],
                'id' => rand(100000000, 999999999),
            ]);

        if (!$response->successful()) {
            Log::warning("⚠️ Metromart Search: API error [{$response->status()}]", ['model' => $model]);
            return null;
        }

        $metromartId = $response->json('result.index.0.id');
        return $metromartId ? self::PRODUCT_URL . $metromartId : null;
    }

    // ============================================
    // Step 2 — Fetch Page
    // ============================================

    private function fetchPage(string $url): ?string
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'ka,en;q=0.9',
                'Referer'         => self::BASE_URL . '/',
            ])
            ->get($url);

        return $response->successful() ? $response->body() : null;
    }

    // ============================================
    // Step 3 — Parse HTML
    // ============================================

    private function parsePage(string $html, string $url): ?array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $name = trim(
            $this->xpathValue($xpath, '//h1[@itemprop="name"]')
                ?: $this->metaContent($xpath, 'og:title')
                ?: ''
        );
        if (!$name) return null;

        $metromartId = basename(parse_url($url, PHP_URL_PATH));

        // ბოლო რიცხვითი ID — slug-ის ტექსტური ნაწილი შეიძლება იცვლებოდეს, მაგრამ ეს მუდმივია
        $metromartNumericId = null;
        if (preg_match('/-(\d+)$/', $metromartId, $idMatch)) {
            $metromartNumericId = $idMatch[1];
        }
        $brand         = $this->xpathAttr($xpath, '//meta[@itemprop="brand"]', 'content');
        $regularPrice  = (float) ($this->metaContent($xpath, 'product:price:amount') ?? 0);
        $salePriceMeta = (float) ($this->metaContent($xpath, 'product:sale_price:amount') ?? 0);

        // Alta-ს ლოგიკა — საიტის ფასდაკლება
        if ($salePriceMeta > 0 && $salePriceMeta < $regularPrice) {
            $discountPrice = $salePriceMeta;
        } else {
            $discountPrice = null;
            if ($salePriceMeta > 0) $regularPrice = $salePriceMeta;
        }

        // Excel-ის ფასი — თუ მოცემულია, override-ავს
        // თუ არ არის — საიტის ფასზე +20% დაერთვის
        if ($this->price !== null && $this->price > 0) {
            $regularPrice  = $this->price;
            $discountPrice = null;
        } else {
            $regularPrice = round($regularPrice * 1.20, 2);
            if ($discountPrice !== null) {
                $discountPrice = round($discountPrice * 1.20, 2);
            }
        }

        return [
            'name'          => $name,
            'brand'         => $brand ? trim($brand) : null,
            'metromartId'   => $metromartId,
            'metromartNumericId' => $metromartNumericId,
            'regularPrice'  => $regularPrice,
            'discountPrice' => $discountPrice,
            'inStock'       => $this->checkTbilisiStock($xpath),
            'images'        => $this->extractImages($xpath, $url),
            'fullSpecs'     => $this->extractFullSpecs($xpath),
            'shortSpecs'    => $this->extractShortSpecs($xpath),
        ];
    }

    // ============================================
    // Step 4 — Import to DB
    // ============================================

    private function importProduct(array $data): void
    {
        $sku      = 'METROMART-' . $data['metromartId'];
        $numericId = $data['metromartNumericId'] ?? null;

        // ჯერ ID-ით ვცადოთ პოვნა (slug-ის ტექსტი შეიძლება შეცვლილიყო, ID მუდმივია)
        $existing = null;
        if ($numericId) {
            $existing = Product::where('sku', 'like', 'METROMART-%')
                ->where('sku', 'like', '%-' . $numericId)
                ->first();
        }

        // fallback — ზუსტი SKU-ით
        if (!$existing) {
            $existing = Product::where('sku', $sku)->first();
        }

        $isNew    = !$existing;
        $brandId  = $this->getBrandId($data['brand']);
        $inStock  = $data['inStock'] ? 1 : 0;

        DB::transaction(function () use ($data, $sku, &$existing, $isNew, $brandId, $inStock) {

            if ($isNew) {
                $existing = Product::create([
                    'sku'           => $sku,
                    'supplier_id'   => self::SUPPLIER_ID,
                    'brand_id'      => $brandId,
                    'category_id'   => self::FALLBACK_CATEGORY_ID,
                    'quantity'      => $inStock,
                    'in_stock'      => $inStock,
                    'show'          => $inStock,
                    'active'        => 1,
                    'main_image'    => null,
                    'update_lock'   => 0,
                    'taxonomy_lock' => 0,
                ]);

                Log::info("➕ Metromart: ახალი", ['sku' => $sku, 'id' => $existing->id]);
            } else {
                if ($existing->update_lock) {
                    Log::info("🔒 Metromart: ჩაკეტილია", ['sku' => $sku]);
                    return;
                }

                $existing->update([
                    'sku'      => $sku, // slug-ის ტექსტი განახლდეს (ID იგივე რჩება)
                    'quantity' => $inStock,
                    'in_stock' => $inStock,
                    'show'     => $inStock,
                    'active'   => 1,
                ]);

                Log::info("🔄 Metromart: განახლდა", ['sku' => $sku, 'id' => $existing->id]);
            }

            // Translations
            foreach (['ka', 'en', 'ru'] as $locale) {
                ProductTranslation::updateOrCreate(
                    ['product_id' => $existing->id, 'locale' => $locale],
                    [
                        'title'    => $data['name'],
                        'slug'     => Str::slug($data['name']) . '-' . $existing->id,
                        'keywords' => null,
                    ]
                );
            }

            ProductPrice::updateOrCreate(
                ['product_id' => $existing->id],
                [
                    'regular_price'    => $this->price ?? $data['regularPrice'],
                ]
            );

            ProductShortSpecification::where('product_id', $existing->id)->forceDelete();
            $rows = [];
            $cnt  = 0;
            foreach ($data['shortSpecs'] as $n => $v) {
                if ($cnt++ >= self::SHORT_SPEC_LIMIT) break;
                $rows[] = ['product_id' => $existing->id, 'name' => $n, 'value' => $v, 'created_at' => now(), 'updated_at' => now()];
            }
            if ($rows) ProductShortSpecification::insert($rows);

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
    // Stock — Tbilisi "მიიღეთ დღეს"
    // ============================================

    private function checkTbilisiStock(DOMXPath $xpath): bool
    {
        $nodes = $xpath->query('//*[contains(@class,"js-availability-button-buy") and contains(@class,"get_today")]');
        return $nodes && $nodes->length > 0;
    }

    // ============================================
    // Specs
    // ============================================

    private function extractFullSpecs(DOMXPath $xpath): array
    {
        $specs        = [];
        $currentGroup = 'Uncategorized';

        $rows = $xpath->query('//section[@id="product_full_spec"]//table//tr');
        if (!$rows) return $specs;

        foreach ($rows as $row) {
            $th = $xpath->query('.//th[@colspan="2"]', $row);
            if ($th && $th->length > 0) {
                $currentGroup = trim($th->item(0)->textContent);
                if (!isset($specs[$currentGroup])) $specs[$currentGroup] = [];
                continue;
            }
            $tds = $xpath->query('.//td', $row);
            if ($tds && $tds->length >= 2) {
                $name  = trim($tds->item(0)->textContent);
                $value = trim($tds->item(1)->textContent);
                if ($name && $value) $specs[$currentGroup][$name] = $value;
            }
        }

        return $specs;
    }

    private function extractShortSpecs(DOMXPath $xpath): array
    {
        $specs = [];
        $brand = $this->xpathAttr($xpath, '//meta[@itemprop="brand"]', 'content');
        if ($brand) $specs['ბრენდი'] = trim($brand);

        $items = $xpath->query('//ul[@id="featuresList"]//dl[contains(@class,"features-item__list")]');
        if ($items) {
            foreach ($items as $item) {
                if (count($specs) >= self::SHORT_SPEC_LIMIT) break;
                $dt = $xpath->query('.//dt', $item);
                $dd = $xpath->query('.//dd', $item);
                if ($dt && $dd && $dt->length > 0 && $dd->length > 0) {
                    $name  = preg_replace('/,\s*[a-zA-Z\/\s]+$/', '', trim($dt->item(0)->textContent));
                    $value = trim($dd->item(0)->textContent);
                    if ($name && $value && !isset($specs[$name])) $specs[$name] = $value;
                }
            }
        }

        return array_slice($specs, 0, self::SHORT_SPEC_LIMIT, true);
    }

    // ============================================
    // Images
    // ============================================

    private function extractImages(DOMXPath $xpath, string $url): array
    {
        $images     = [];
        $templateId = $this->extractTemplateId($url);

        if ($templateId) {
            $images[] = self::BASE_URL . "/web/image/product.template/{$templateId}/image";
        }

        $galleryImgs = $xpath->query('//div[@id="o-carousel-product"]//div[contains(@class,"item")]//img');
        if ($galleryImgs) {
            foreach ($galleryImgs as $img) {
                $src = $img->getAttribute('data-zoom-image') ?: $img->getAttribute('src');
                if ($src) {
                    if (str_starts_with($src, '/')) $src = self::BASE_URL . $src;
                    $src = preg_replace('#/\d+x\d+$#', '', $src);
                    if (!in_array($src, $images)) $images[] = $src;
                }
            }
        }

        return array_values(array_unique(array_filter($images)));
    }

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
            if (in_array($imageUrl, $processedUrls)) continue;
            $processedUrls[] = $imageUrl;

            try {
                $response = Http::timeout(30)->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'Referer'    => self::BASE_URL . '/',
                ])->get($imageUrl);

                if (!$response->successful()) continue;
                if (strlen($response->body()) > self::MAX_IMAGE_SIZE) continue;

                $ext      = $this->getImageExtension($imageUrl);
                $path     = "uploads/products/{$product->id}/" . Str::random(40) . ".{$ext}";
                Storage::disk('public')->put($path, $response->body());

                if (!$mainSet) {
                    $product->update(['main_image' => $path]);
                    $mainSet = true;
                } else {
                    $gallery[] = ['product_id' => $product->id, 'path' => $path, 'created_at' => now(), 'updated_at' => now()];
                }
            } catch (Exception $e) {
                Log::warning("⚠️ Metromart: სურათი ვერ ჩამოიტვირთა", ['url' => $imageUrl, 'error' => $e->getMessage()]);
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
                    fn ($q) => $q->whereRaw('LOWER(TRIM(title)) = ?', [$normalized])
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

                Log::info("✨ Metromart: ახალი ბრენდი '{$brandName}', id={$new->id}");
                return $new->id;
            }
        );
    }

    // ============================================
    // Helpers
    // ============================================

    private function extractTemplateId(string $url): ?string
    {
        if (preg_match('/-(\d+)$/', basename(parse_url($url, PHP_URL_PATH)), $m)) {
            return $m[1];
        }
        return null;
    }

    private function metaContent(DOMXPath $xpath, string $property): ?string
    {
        foreach (["//meta[@property=\"{$property}\"]/@content", "//meta[@name=\"{$property}\"]/@content"] as $q) {
            $node = $xpath->query($q);
            if ($node && $node->length > 0) return trim($node->item(0)->nodeValue);
        }
        return null;
    }

    private function xpathValue(DOMXPath $xpath, string $query): ?string
    {
        $node = $xpath->query($query);
        return ($node && $node->length > 0) ? trim($node->item(0)->textContent) : null;
    }

    private function xpathAttr(DOMXPath $xpath, string $query, string $attr): ?string
    {
        $node = $xpath->query($query);
        return ($node && $node->length > 0) ? trim($node->item(0)->getAttribute($attr)) : null;
    }

    private function getImageExtension(string $url): string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']) ? $ext : 'jpg';
    }

    public function failed(Exception $exception): void
    {
        Log::error("🚨 MetromartProductJob permanently failed", [
            'model' => $this->model,
            'error' => $exception->getMessage(),
        ]);
    }
}