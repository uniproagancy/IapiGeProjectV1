<?php

namespace App\Jobs;

use App\Models\Product\Product;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductShortSpecification;
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

class AlneoImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    private const SUPPLIER_ID      = 10;
    private const BRAND_ID         = 1;
    private const CATEGORY_ID      = 203;
    private const SHORT_SPEC_LIMIT = 5;

    public function __construct(public string $url) {}

    public function handle(): void
    {
        @ini_set('memory_limit', '256M');

        try {
            $response = Http::timeout(45)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($this->url);

            if (!$response->successful()) {
                Log::warning("⛔ Alneo: გვერდი არ მუშაობს", ['url' => $this->url, 'status' => $response->status()]);
                return;
            }

            $html = $response->body();
            $data = $this->extractProductJson($html);

            if (!$data) {
                Log::warning("⛔ Alneo: ld+json ვერ მოიძებნა", ['url' => $this->url]);
                return;
            }

            // ============ მონაცემების ამოღება ============
            $name   = $this->cleanTitle($data['name'] ?? '');
            $rawSku = trim((string)($data['sku'] ?? ''));

            if (!$name || !$rawSku) {
                Log::warning("⛔ Alneo: name/sku ცარიელია", ['url' => $this->url]);
                return;
            }

            $sku = 'ALNEO-' . $rawSku;

            [$regularPrice, $discountPrice] = $this->extractPrices($data);

            if ($regularPrice <= 0) {
                Log::warning("⛔ Alneo: ფასი 0", ['url' => $this->url]);
                return;
            }

            $description = $this->extractDescription($html);
            $images      = $this->extractImages($html, $data);

            if (empty($images)) {
                Log::warning("⚠️ Alneo: სურათები ვერ მოიძებნა", ['url' => $this->url, 'sku' => $sku]);
            } else {
                Log::info("🖼️ Alneo: ნაპოვნია " . count($images) . " სურათი", ['sku' => $sku]);
            }

            $shortSpecs = $this->extractShortSpecs($html);

            $availability = (string)($data['offers'][0]['availability'] ?? '');
            $inStock = str_contains(strtolower($availability), 'instock') ? 1 : 0;

            // ============ Product upsert ============
            $product = Product::where('sku', $sku)->first();
            $isNew   = !$product;

            if ($isNew) {
                $product = Product::create([
                    'sku'           => $sku,
                    'supplier_id'   => self::SUPPLIER_ID,
                    'brand_id'      => self::BRAND_ID,
                    'category_id'   => self::CATEGORY_ID,
                    'quantity'      => $inStock ? 10 : 0,
                    'in_stock'      => $inStock,
                    'show'          => $inStock,
                    'active'        => 1,
                    'main_image'    => null,
                    'update_lock'   => 0,
                    'taxonomy_lock' => 0,
                ]);
                Log::info("➕ Alneo: ახალი პროდუქტი", ['sku' => $sku, 'id' => $product->id]);
            } else {
                $product->update([
                    'quantity' => $inStock ? max($product->quantity, 10) : 0,
                    'in_stock' => $inStock,
                    'show'     => $inStock,
                ]);
                Log::info("🔄 Alneo: განახლდა", ['sku' => $sku, 'id' => $product->id]);
            }

            // ============ Translation (ka) ============
            ProductTranslation::updateOrCreate(
                ['product_id' => $product->id, 'locale' => 'ka'],
                [
                    'title'       => $name,
                    'slug'        => Str::slug($name) . '-' . $product->id,
                    'description' => $description,
                ]
            );

            // ============ Price ============
            ProductPrice::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'dealer_price'     => $regularPrice,
                    'regular_price'    => $regularPrice,
                    'discount_price'   => $discountPrice,
                    'discount_percent' => $discountPrice > 0 && $regularPrice > 0
                        ? (int) round((($regularPrice - $discountPrice) / $regularPrice) * 100)
                        : 0,
                ]
            );

            // ============ Short Specs (only on create) ============
            if ($isNew && !empty($shortSpecs)) {
                $sortOrder = 0;
                foreach ($shortSpecs as $key => $value) {
                    ProductShortSpecification::create([
                        'product_id' => $product->id,
                        'locale'     => 'ka',
                        'name'       => $key,
                        'value'      => $value,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            // ============ Main image (only if empty) ============
            if (empty($product->main_image) && !empty($images)) {
                $localPath = $this->downloadImage($images[0], $product->id);
                if ($localPath) {
                    $product->update(['main_image' => $localPath]);
                    Log::info("📸 Alneo: main_image შენახულია", ['sku' => $sku, 'path' => $localPath]);
                }
            }

        } catch (\Throwable $e) {
            Log::error("❌ Alneo Import შეცდომა", [
                'url'   => $this->url,
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
            ]);
            throw $e;
        }
    }

    private function extractProductJson(string $html): ?array
    {
        if (!preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $jsonRaw) {
            $decoded = json_decode(trim($jsonRaw), true);
            if (!$decoded) continue;

            if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
                foreach ($decoded['@graph'] as $item) {
                    if (($item['@type'] ?? '') === 'Product') return $item;
                }
            }

            if (($decoded['@type'] ?? '') === 'Product') return $decoded;
        }

        return null;
    }

    private function extractPrices(array $data): array
    {
        $offer     = $data['offers'][0] ?? [];
        $specs     = $offer['priceSpecification'] ?? [];
        $mainPrice = (float)($offer['price'] ?? 0);
        $listPrice = 0.0;
        $unitPrice = 0.0;

        foreach ($specs as $spec) {
            $price     = (float)($spec['price'] ?? 0);
            $priceType = (string)($spec['priceType'] ?? '');

            if (str_contains($priceType, 'ListPrice')) {
                $listPrice = $price;
            } else {
                $unitPrice = $price;
            }
        }

        if ($listPrice > 0 && $unitPrice > 0 && $listPrice > $unitPrice) {
            return [$listPrice, $unitPrice];
        }

        $regular = $unitPrice > 0 ? $unitPrice : $mainPrice;
        return [$regular, 0.0];
    }

    private function extractDescription(string $html): string
    {
        $parts = [];

        if (preg_match(
            '/<div class="woocommerce-product-details__short-description">(.*?)<\/div>/is',
            $html, $m
        )) {
            $parts[] = trim($m[1]);
        }

        if (preg_match(
            '/<div class="electro-description[^"]*">(.*?)<\/div>\s*<div class="product_meta">/is',
            $html, $m
        )) {
            $parts[] = trim($m[1]);
        }

        $combined = implode("\n\n", array_filter($parts));
        return $this->cleanDescription($combined);
    }

    private function extractImages(string $html, array $data): array
    {
        $images = [];

        if (!empty($data['image'])) {
            if (is_string($data['image'])) {
                $images[] = $data['image'];
            } elseif (is_array($data['image'])) {
                foreach ($data['image'] as $img) {
                    if (is_string($img)) $images[] = $img;
                    elseif (is_array($img) && !empty($img['url'])) $images[] = $img['url'];
                }
            }
        }

        $patterns = [
            '/<(?:div|figure)[^>]*class="[^"]*woocommerce-product-gallery__image[^"]*"[^>]*>\s*<a[^>]+href="([^"]+)"/is',
            "/<(?:div|figure)[^>]*class='[^']*woocommerce-product-gallery__image[^']*'[^>]*>\s*<a[^>]+href='([^']+)'/is",
            '/data-large_image=["\']([^"\']+)["\']/i',
            '/data-large-image=["\']([^"\']+)["\']/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] as $href) {
                    if (preg_match('/\.(jpe?g|png|webp)(\?|$|#)/i', $href)) {
                        $images[] = $href;
                    }
                }
            }
        }

        if (preg_match('/<img[^>]+class="[^"]*wp-post-image[^"]*"[^>]+src="([^"]+)"/i', $html, $m)) {
            if (preg_match('/\.(jpe?g|png|webp)(\?|$|#)/i', $m[1])) {
                $fullUrl  = preg_replace('/-\d+x\d+(\.(jpe?g|png|webp))$/i', '$1', $m[1]);
                $images[] = $fullUrl;
                $images[] = $m[1];
            }
        }

        $unique = array_values(array_unique($images));

        return array_values(array_filter($unique, function ($url) {
            return filter_var($url, FILTER_VALIDATE_URL) !== false
                && preg_match('/\.(jpe?g|png|webp)(\?|$|#)/i', $url);
        }));
    }

    private function extractShortSpecs(string $html): array
    {
        $specs = [];

        if (preg_match_all(
            '/<div class="mb-3\.5 grid grid-cols-2[^"]*">\s*<span[^>]*>([^<]+)<\/span>\s*<span[^>]*>([^<]+)<\/span>\s*<\/div>/is',
            $html, $matches, PREG_SET_ORDER
        )) {
            foreach ($matches as $m) {
                $key   = trim(rtrim(strip_tags($m[1]), ':'));
                $value = trim(strip_tags($m[2]));
                if ($key && $value && !isset($specs[$key])) {
                    $specs[$key] = $value;
                    if (count($specs) >= self::SHORT_SPEC_LIMIT) break;
                }
            }
        }

        if (empty($specs) && preg_match(
                '/<table[^>]*class="[^"]*shop_attributes[^"]*"[^>]*>(.*?)<\/table>/is',
                $html, $tableMatch
            )) {
            if (preg_match_all(
                '/<tr[^>]*>\s*<th[^>]*>(.*?)<\/th>\s*<td[^>]*>(.*?)<\/td>\s*<\/tr>/is',
                $tableMatch[1], $rows, PREG_SET_ORDER
            )) {
                foreach ($rows as $row) {
                    $key   = trim(strip_tags($row[1]));
                    $value = trim(strip_tags($row[2]));
                    if ($key && $value && !isset($specs[$key])) {
                        $specs[$key] = $value;
                        if (count($specs) >= self::SHORT_SPEC_LIMIT) break;
                    }
                }
            }
        }

        return $specs;
    }

    private function downloadImage(string $url, int $productId): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Referer'    => 'https://alneo.ge/',
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::warning("⚠️ Alneo: სურათი ვერ ჩამოიტვირთა", ['url' => $url, 'status' => $response->status()]);
                return null;
            }

            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg');
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) $ext = 'jpg';

            $filename = 'main_' . time() . '.' . $ext;
            $path     = "uploads/products/{$productId}/{$filename}";

            Storage::disk('public')->put($path, $response->body());

            return $path;

        } catch (\Throwable $e) {
            Log::warning("⚠️ Alneo: სურათი ვერ ჩამოიტვირთა", ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * სათაური — მხოლოდ html entities დეკოდება, სხვა ცვლილება არ
     */
    private function cleanTitle(string $title): string
    {
        return trim(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * აღწერა — მხოლოდ alneo.ge → iapi.ge (standalone alneo არ შეიცვლება)
     */
    private function cleanDescription(string $text): string
    {
        $text = preg_replace('/alneo\.com\.ge/i', 'iapi.ge', $text);
        $text = preg_replace('/alneo\.ge/i', 'iapi.ge', $text);
        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}