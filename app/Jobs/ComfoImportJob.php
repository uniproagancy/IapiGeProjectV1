<?php

namespace App\Jobs;

use App\Models\Product\Product;
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

class ComfoImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    // ⚠️ თუ Comfo-ს supplier_id სხვა გაქვს, აქ შეცვალე
    private const SUPPLIER_ID = 9;

    // ქართული → ლათინური მხოლოდ URL-ისთვის
    private const KA_TO_EN = [
        'ა'=>'a','ბ'=>'b','გ'=>'g','დ'=>'d','ე'=>'e','ვ'=>'v','ზ'=>'z','თ'=>'t',
        'ი'=>'i','კ'=>'k','ლ'=>'l','მ'=>'m','ნ'=>'n','ო'=>'o','პ'=>'p','ჟ'=>'zh',
        'რ'=>'r','ს'=>'s','ტ'=>'t','უ'=>'u','ფ'=>'p','ქ'=>'k','ღ'=>'gh','ყ'=>'y',
        'შ'=>'sh','ჩ'=>'ch','ც'=>'ts','ძ'=>'dz','წ'=>'w','ჭ'=>'ch','ხ'=>'kh',
        'ჯ'=>'j','ჰ'=>'h',
    ];

    public function __construct(
        public string $externalId,   // Excel ID სვეტი (მაგ. "534")
        public int    $stock,        // Excel რაოდენობა (50+ → 50)
        public string $url           // Excel Product Link
    ) {}

    public function handle(): void
    {
        @ini_set('memory_limit', '256M');

        $sku = 'COMFO-' . $this->externalId;

        try {
            // 1) ცადე ორიგინალი URL → თუ 404, ცადე transliterated
            $html = $this->fetchHtml($this->url);

            if (!$html) {
                $altUrl = $this->transliterateUrl($this->url);

                if ($altUrl !== $this->url) {
                    Log::info("🔁 Comfo: ცადე transliterated URL", ['from' => $this->url, 'to' => $altUrl]);
                    $html = $this->fetchHtml($altUrl);
                }
            }

            if (!$html) {
                Log::warning("⛔ Comfo: ვერ მოძებნა (404)", ['sku' => $sku, 'url' => $this->url]);
                return;
            }

            // 2) ld+json პროდუქტი
            $data = $this->extractProductJson($html);

            if (!$data) {
                Log::warning("⛔ Comfo: ld+json ვერ მოიძებნა", ['sku' => $sku]);
                return;
            }

            // 3) ფასი + markup
            $sitePrice  = (float)($data['offers'][0]['price'] ?? 0);
            if ($sitePrice <= 0) {
                Log::warning("⛔ Comfo: ფასი 0 ან ცარიელი", ['sku' => $sku]);
                return;
            }
            $finalPrice = $sitePrice < 100 ? $sitePrice + 50 : $sitePrice + 100;

            // 4) title + description
            $title       = $this->cleanText($data['name'] ?? '');
            $description = $this->cleanText($data['description'] ?? '');

            if (empty($title)) {
                Log::warning("⛔ Comfo: სათაური ცარიელია", ['sku' => $sku]);
                return;
            }

            // 5) სურათები
            $images = is_array($data['image'] ?? null) ? $data['image'] : [$data['image'] ?? null];
            $images = array_filter($images);

            // 6) availability
            $availability = $data['offers'][0]['availability'] ?? '';
            $inStock      = $this->stock > 0 && stripos($availability, 'InStock') !== false;

            // 7) Product upsert
            $product = Product::where('sku', $sku)->first();

            if ($product) {
                // განახლება — taxonomy/brand-ი არ ვცვლი
                $product->update([
                    'quantity' => $this->stock,
                    'in_stock' => $inStock ? 1 : 0,
                    'show'     => $inStock ? 1 : 0,
                    'active'   => 1,
                ]);
            } else {
                // ახალი
                $product = Product::create([
                    'sku'         => $sku,
                    'supplier_id' => self::SUPPLIER_ID,
                    'quantity'    => $this->stock,
                    'in_stock'    => $inStock ? 1 : 0,
                    'show'        => $inStock ? 1 : 0,
                    'active'      => 1,
                    'category_id' => null,
                    'brand_id'    => null,
                ]);
            }

            // 8) Translation
            ProductTranslation::updateOrCreate(
                ['product_id' => $product->id, 'locale' => 'ka'],
                [
                    'title'       => $title,
                    'slug'        => Str::slug($title) ?: ('comfo-' . $this->externalId),
                    'description' => $description,
                ]
            );

            // 9) ფასი (regular = ფასიდან +markup, discount = null)
            ProductPrice::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'dealer_price'   => $sitePrice,
                    'regular_price'  => $finalPrice,
                    'discount_price' => null,
                ]
            );

            // 10) მთავარი სურათი (მხოლოდ თუ ცარიელია)
            if (empty($product->main_image) && !empty($images[0])) {
                $imagePath = $this->downloadImage($images[0], $product->id);
                if ($imagePath) {
                    $product->update(['main_image' => $imagePath]);
                }
            }

            Log::info("✅ Comfo იმპორტი წარმატებული", [
                'sku'         => $sku,
                'title'       => $title,
                'site_price'  => $sitePrice,
                'final_price' => $finalPrice,
                'stock'       => $this->stock,
            ]);

        } catch (\Throwable $e) {
            Log::error("❌ Comfo იმპორტი ჩავარდა", [
                'sku'   => $sku,
                'url'   => $this->url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * URL-ის წამოღება + 404 detection
     */
    private function fetchHtml(string $url): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($url);

            if ($response->status() === 404 || $response->status() >= 400) {
                return null;
            }

            return $response->body();
        } catch (\Throwable $e) {
            Log::warning("Comfo fetch შეცდომა", ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * URL-ში ქართული ნაწილი transliterate (ბოლო segment)
     */
    private function transliterateUrl(string $url): string
    {
        $hasKa = preg_match('/[\x{10A0}-\x{10FF}]/u', $url);
        if (!$hasKa) {
            return $url;
        }

        $converted = strtr($url, self::KA_TO_EN);
        // უსაფრთხო ნაცვლად, თუ რამე ქართული დარჩა
        $converted = preg_replace_callback('/[\x{10A0}-\x{10FF}]/u', function ($m) {
            return self::KA_TO_EN[$m[0]] ?? '';
        }, $converted);

        return $converted;
    }

    /**
     * ld+json პროდუქტი
     */
    private function extractProductJson(string $html): ?array
    {
        if (!preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $jsonStr) {
            $data = json_decode(trim($jsonStr), true);

            if (!is_array($data)) continue;

            $type = $data['@type'] ?? '';
            if (is_string($type) && (str_contains($type, 'Product') || $type === 'http://schema.org/Product')) {
                return $data;
            }

            // @graph არრეი — საჭიროების შემთხვევაში
            if (!empty($data['@graph']) && is_array($data['@graph'])) {
                foreach ($data['@graph'] as $g) {
                    $t = $g['@type'] ?? '';
                    if (is_string($t) && str_contains($t, 'Product')) {
                        return $g;
                    }
                }
            }
        }

        return null;
    }

    /**
     * ტექსტის გასუფთავება
     */
    private function cleanText(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/\s+/u', ' ', $text);
        // ბრენდის ცვლილება (Comfo → iapi.ge)
        $text = str_ireplace(['comfo.ge', 'comfo'], 'iapi.ge', $text);
        return trim($text);
    }

    /**
     * სურათის ჩამოტვირთვა
     */
    private function downloadImage(string $imageUrl, int $productId): ?string
    {
        try {
            $response = Http::timeout(60)->get($imageUrl);

            if (!$response->successful()) {
                return null;
            }

            $ext      = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'uploads/products/' . $productId . '/' . Str::random(16) . '.' . $ext;

            Storage::disk('public')->put($filename, $response->body());

            return $filename;
        } catch (\Throwable $e) {
            Log::warning("Comfo სურათი ვერ ჩამოიტვირთა", ['url' => $imageUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }
}