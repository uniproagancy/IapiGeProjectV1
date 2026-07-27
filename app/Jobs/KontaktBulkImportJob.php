<?php

namespace App\Jobs;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductBrandTranslation;
use App\Models\Product\ProductFullSpecificationItem;
use App\Models\Product\ProductFullSpecificationSection;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductShortSpecification;
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

class KontaktBulkImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1; // bulk job-ს retry არ ვურჩევთ — ნახევრად დამუშავებულს თავიდან წამოიღებს
    public int $timeout = 3600; // 1 საათი — მოირგეთ თქვენი ფაილის ზომაზე
    public int $backoff = 30;

    private const SUPPLIER_ID      = 8;
    private const SKU_PREFIX       = 'KONTAKT-';
    private const DEFAULT_CATEGORY = 6;
    private const DEFAULT_BRAND    = 6;
    private const SHORT_SPEC_LIMIT = 5;

    /**
     * @param array $rows [['model' => ..., 'url' => ..., 'stock' => ..., 'price' => ..., 'discount_price' => ...], ...]
     */
    public function __construct(public array $rows) {}

    public function handle(): void
    {
        @ini_set('memory_limit', '512M');

        Log::info("🚀 Kontakt Bulk Import: დაიწყო — " . count($this->rows) . " პროდუქტი");

        $scannedSkus = [];
        $imported = 0;
        $failed = 0;

        foreach ($this->rows as $row) {
            $sku = self::SKU_PREFIX . $row['model'];
            $scannedSkus[] = $sku;

            try {
                $this->importOne($row);
                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error("❌ Kontakt Bulk: შეცდომა [{$row['model']}]: " . $e->getMessage());
                // ერთი პროდუქტის ჩავარდნა ნუ გაჩერებს მთელ batch-ს
                continue;
            }
        }

        Log::info("✅ Kontakt Bulk Import: დასრულდა — imported={$imported}, failed={$failed}");

        $this->deactivateMissing($scannedSkus);
    }

    private function importOne(array $row): void
    {
        $model = $row['model'];
        $url = $this->normalizeUrl($row['url']);
        $price = $row['price'] ?? null;
        $discountPriceInput = $row['discount_price'] ?? null;

        // ექსელის მე-2 სვეტი (რაოდენობა) შეიძლება იყოს: "0.00", "6.00", "10+", "Н/Д" და ა.შ.
        $parsedStock = $this->parseStockValue($row['stock'] ?? null);

        if (empty($url) || !str_starts_with($url, 'http')) {
            Log::warning("⚠️ Kontakt: არასწორი URL — {$model}");
            return;
        }

        $response = Http::timeout(30)->withHeaders([
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',
            'Accept-Language' => 'ka',
        ])->get($url);

        if (!$response->successful()) {
            Log::warning("⚠️ Kontakt: გვერდი ვერ გაიხსნა ({$response->status()}) — {$url}");
            return;
        }

        $html = $response->body();

        $jsonData = $this->extractProductJson($html);
        if (!$jsonData) {
            Log::warning("⚠️ Kontakt: ld+json Product ვერ მოიძებნა — {$url}");
            return;
        }

        $title        = $this->cleanBrandText($this->cleanTitle($jsonData['name'] ?? $model));
        $price        = $price ?? (float) ($jsonData['offers']['price'] ?? 0);
        $availability = $jsonData['offers']['availability'] ?? '';
        $image        = $jsonData['image'] ?? null;
        $brandName    = $jsonData['brand']['name'] ?? null;
        $desc         = $this->cleanBrandText($jsonData['description'] ?? null);

        // საბოლოო in_stock: გვერდზე უნდა ეწეროს InStock ᲓᲐ ექსელის სვეტი არ უნდა იყოს 0/Н/Д
        $inStock = str_contains($availability, 'InStock') && $parsedStock['available'];
        $stock   = $parsedStock['qty'];

        if ($price <= 0) {
            Log::warning("⚠️ Kontakt: ფასი 0 — გამოტოვება — {$model}");
            return;
        }

        $discountPrice = $discountPriceInput;
        $hasDiscount = $discountPrice !== null && $discountPrice > 0 && $discountPrice < $price;

        $specs = $this->extractSpecs($html);
        unset($html);

        $sku      = self::SKU_PREFIX . $model;
        $existing = Product::where('sku', $sku)->first();

        if ($existing && $existing->update_lock) {
            Log::info("🔒 Kontakt: locked, skip — {$sku}");
            return;
        }

        DB::transaction(function () use ($sku, $existing, $title, $price, $inStock, $image, $brandName, $desc, $specs, $hasDiscount, $discountPrice, $stock) {
            $brandId = $this->resolveBrand($brandName);

            $stockQty = $inStock ? max($stock, 1) : 0;
            $showVal  = $inStock ? 1 : 0;

            if ($existing) {
                $product = $existing;

                $product->update([
                    'quantity' => $stockQty,
                    'in_stock' => $inStock ? 1 : 0,
                    'show'     => $showVal,
                    'active'   => 1,
                ]);

                Log::info("🔁 Kontakt: updated {$sku} (stock={$stockQty})");
            } else {
                $product = Product::create([
                    'supplier_product_id' => null,
                    'brand_id'            => $brandId,
                    'category_id'         => self::DEFAULT_CATEGORY,
                    'sku'                 => $sku,
                    'supplier_id'         => self::SUPPLIER_ID,
                    'main_image'          => null,
                    'active'              => 1,
                    'quantity'            => $stockQty,
                    'in_stock'            => $inStock ? 1 : 0,
                    'show'                => $showVal,
                ]);
                Log::info("✨ Kontakt: created {$sku} (id={$product->id}, stock={$stockQty})");
            }

            $discountPercent = $hasDiscount
                ? (int) round((($price - $discountPrice) / $price) * 100)
                : 0;

            ProductPrice::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'regular_price'    => $price,
                    'dealer_price'     => $price,
                    'discount_price'   => $hasDiscount ? $discountPrice : null,
                    'discount_percent' => $discountPercent,
                ]
            );

            $slug = Str::slug($title, '-') . '-' . $product->id;
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

            if (!empty($image) && empty($product->main_image)) {
                $this->downloadImage($product, $image);
            }

            $this->saveSpecifications($product, $specs);

            Log::info("✅ Kontakt saved: {$sku} (brand={$brandId}, specs=" . count($specs) . ")");
        });
    }

    private function deactivateMissing(array $scannedSkus): void
    {
        if (empty($scannedSkus)) {
            Log::warning("⚠️ Kontakt Bulk: scannedSkus ცარიელია, გამორთვა გამოტოვებულია");
            return;
        }

        $missingProducts = Product::where('supplier_id', self::SUPPLIER_ID)
            ->where('active', 1)
            ->where('update_lock', 0)
            ->whereNotIn('sku', $scannedSkus)
            ->get();

        if ($missingProducts->isEmpty()) {
            Log::info("✅ Kontakt Bulk: გამქრალი პროდუქტები არ არის");
            return;
        }

        Product::whereIn('id', $missingProducts->pluck('id'))
            ->update([
                'active'   => 0,
                'show'     => 0,
                'in_stock' => 0,
                'quantity' => 0,
            ]);

        Log::info("🚫 Kontakt Bulk: გაითიშა " . $missingProducts->count() . " პროდუქტი: "
            . $missingProducts->pluck('sku')->implode(', '));
    }

    private function saveSpecifications(Product $product, array $specs): void
    {
        $sectionIds = ProductFullSpecificationSection::where('product_id', $product->id)->pluck('id');
        ProductFullSpecificationItem::whereIn('section_id', $sectionIds)->forceDelete();
        ProductFullSpecificationSection::where('product_id', $product->id)->forceDelete();
        ProductShortSpecification::where('product_id', $product->id)->forceDelete();

        if (empty($specs)) return;

        $section = ProductFullSpecificationSection::create([
            'product_id' => $product->id,
            'name'       => 'მახასიათებლები',
        ]);

        foreach ($specs as $spec) {
            ProductFullSpecificationItem::create([
                'section_id' => $section->id,
                'name'       => $spec['name'],
                'value'      => $spec['value'],
                'filter'     => 0,
            ]);
        }

        $shortSpecs = array_slice($specs, 0, self::SHORT_SPEC_LIMIT);
        $shortRows  = [];
        foreach ($shortSpecs as $spec) {
            $shortRows[] = [
                'product_id' => $product->id,
                'name'       => $spec['name'],
                'value'      => $spec['value'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($shortRows)) {
            ProductShortSpecification::insert($shortRows);
        }
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        return preg_replace('#^(https?://[^/]+)/(en|ka|ru)(/|$)#i', '$1/', $url);
    }

    private function extractProductJson(string $html): ?array
    {
        if (!preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $block) {
            $data = json_decode(trim($block), true);
            if (!is_array($data)) continue;

            if (($data['@type'] ?? '') === 'Product') return $data;

            if (isset($data['@graph']) && is_array($data['@graph'])) {
                foreach ($data['@graph'] as $item) {
                    if (($item['@type'] ?? '') === 'Product') return $item;
                }
            }
        }

        return null;
    }

    private function extractSpecs(string $html): array
    {
        $specs = [];
        $pattern = '/<div class="har__title">(.*?)<\/div>\s*<div class="har__znach">(.*?)<\/div>/s';

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $name  = trim(strip_tags($m[1]));
                $value = trim(strip_tags($m[2]));

                $name  = preg_replace('/\s+/u', ' ', $name);
                $value = preg_replace('/\s+/u', ' ', $value);

                if ($name === '' || $value === '' || $value === '-'
                    || mb_strpos($value, 'მიუწვდომელია') !== false) {
                    continue;
                }

                $specs[] = ['name' => $name, 'value' => $value];
            }
        }

        return $specs;
    }

    private function cleanTitle(string $title): string
    {
        $title = preg_replace('/\s*\|\s*Kontakt\.ge\s*$/i', '', $title);
        return trim($title);
    }

    private function cleanBrandText(?string $text): ?string
    {
        if (empty($text)) return $text;

        $text = preg_replace('/\b(?:www\.)?kontakt\.ge\b/i', 'iapi.ge', $text);
        $text = preg_replace('/\bkontakt\b/i', 'iapi', $text);
        $text = preg_replace('/კონტაქტ(ი|ის|ში|იდან)?/u', 'იაპი', $text);
        $text = preg_replace('/შიდა\s*განვადება/u', '', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = preg_replace('/\s*([.,;])\s*\1+/u', '$1', $text);
        $text = trim($text, " \t\n\r\0\x0B.,;-");

        return trim($text);
    }

    /**
     * ამუშავებს ექსელის "რაოდენობის" სვეტს, რომელიც შეიძლება მოვიდეს სხვადასხვა ფორმატში:
     * "0.00", "6.00", "10+", "Н/Д", "N/A" და ა.შ.
     *
     * "10+" აღნიშნავს "10 ან მეტს" — ვიღებთ რიცხვს (10) როგორც მინიმალურ მარაგს.
     * "0.00" ან "Н/Д"/"N/A" ტიპის მნიშვნელობებზე პროდუქტი მიიჩნევა არასაწყობოდ.
     *
     * @return array{qty: int, available: bool}
     */
    private function parseStockValue(mixed $raw): array
    {
        if ($raw === null) {
            return ['qty' => 0, 'available' => false];
        }

        $value = trim((string) $raw);

        if ($value === '' || $this->isNoDataStockValue($value)) {
            return ['qty' => 0, 'available' => false];
        }

        // "10+" ტიპის მნიშვნელობა
        if (str_ends_with($value, '+')) {
            $qty = (int) preg_replace('/[^\d]/', '', $value);
            return ['qty' => max($qty, 1), 'available' => $qty > 0];
        }

        // ჩვეულებრივი რიცხვი, მაგ. "0.00", "6.00", "6,00"
        $numericStr = preg_replace('/[^\d.,]/', '', $value);
        $numericStr = str_replace(',', '.', $numericStr);
        $numeric    = $numericStr === '' ? 0.0 : (float) $numericStr;

        return ['qty' => (int) $numeric, 'available' => $numeric > 0];
    }

    /**
     * ამოწმებს არის თუ არა მნიშვნელობა "მონაცემი არ არის" ტიპის (Н/Д, N/A და ა.შ.),
     * non-breaking space-ების და სხვა უხილავი სიმბოლოების გათვალისწინებით.
     */
    private function isNoDataStockValue(string $value): bool
    {
        $normalized = preg_replace('/[\x{00A0}\s]+/u', '', $value);
        $normalized = mb_strtoupper($normalized);

        return in_array($normalized, ['Н/Д', 'НД', 'N/A', 'NA', 'N\A'], true);
    }

    private function resolveBrand(?string $brandName): int
    {
        if (empty($brandName)) return self::DEFAULT_BRAND;

        $normalized = mb_strtolower(trim($brandName));

        $brand = ProductBrand::whereHas('translations',
            fn ($q) => $q->whereRaw('LOWER(TRIM(title)) = ?', [$normalized])
        )->first();

        if ($brand) return $brand->id;

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

        Log::info("✨ Kontakt: new brand '{$brandName}' id={$newBrand->id}");
        return $newBrand->id;
    }

    private function downloadImage(Product $product, string $url): void
    {
        try {
            $resp = Http::timeout(30)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'Referer'    => 'https://kontakt.ge/',
            ])->get($url);

            if (!$resp->successful()) {
                Log::warning("⚠️ Kontakt image download failed: {$url}");
                return;
            }

            $ext      = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = Str::random(40) . '.' . strtolower($ext);
            $path     = "uploads/products/{$product->id}/{$filename}";

            Storage::disk('public')->put($path, $resp->body());
            $product->update(['main_image' => $path]);

            Log::info("📦 Kontakt image saved for product {$product->id}");
        } catch (Exception $e) {
            Log::warning("⚠️ Kontakt image: " . $e->getMessage());
        }
    }
}