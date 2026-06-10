<?php

namespace App\Jobs;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductBrandTranslation;
use App\Models\Product\ProductFullSpecificationItem;
use App\Models\Product\ProductFullSpecificationSection;
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
use Symfony\Component\DomCrawler\Crawler;
use Exception;

class KontaktImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;
    public int $backoff = 30;

    private const SUPPLIER_ID      = 8;
    private const SKU_PREFIX       = 'KONTAKT-';
    private const DEFAULT_CATEGORY = 6;
    private const DEFAULT_BRAND    = 6;

    public function __construct(
        public string $model,      // Excel დასახელება (SKU-სთვის)
        public string $url,        // kontakt.ge ლინკი
        public int $stock = 0,     // Excel stock (fallback)
        public ?float $price = null
    ) {
    }

    public function handle(): void
    {
        $url = $this->normalizeUrl($this->url);

        if (empty($url) || !str_starts_with($url, 'http')) {
            Log::warning("⚠️ Kontakt: არასწორი URL — {$this->model} ({$this->url})");
            return;
        }

        Log::info("🔄 Kontakt job START: '{$this->model}' → {$url}");

        try {
            $response = Http::timeout(30)->withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',
                'Accept-Language' => 'ka',
            ])->get($url);

            if (!$response->successful()) {
                Log::warning("⚠️ Kontakt: გვერდი ვერ გაიხსნა ({$response->status()}) — {$url}");
                return;
            }

            $html = $response->body();

            // === 1. ld+json (Product) ===
            $jsonData = $this->extractProductJson($html);
            if (!$jsonData) {
                Log::warning("⚠️ Kontakt: ld+json Product ვერ მოიძებნა — {$url}");
                return;
            }

            $title    = $this->cleanTitle($jsonData['name'] ?? $this->model);
            $price     = $this->price ?? (float) ($jsonData['offers']['price'] ?? 0);
            $availability = $jsonData['offers']['availability'] ?? '';
            $inStock   = str_contains($availability, 'InStock');
            $image     = $jsonData['image'] ?? null;
            $brandName = $jsonData['brand']['name'] ?? null;
            $desc      = $jsonData['description'] ?? null;

            if ($price <= 0) {
                Log::warning("⚠️ Kontakt: ფასი 0 — გამოტოვება — {$this->model}");
                return;
            }

            // === 2. სპეციფიკაციები (.har) ===
            $specs = $this->extractSpecs($html);

            // === 3. ჩაწერა ===
            $sku      = self::SKU_PREFIX . $this->model;
            $existing = Product::where('sku', $sku)->first();

            if ($existing && $existing->update_lock) {
                Log::info("🔒 Kontakt: locked, skip — {$sku}");
                return;
            }

            DB::transaction(function () use ($sku, $existing, $title, $price, $inStock, $image, $brandName, $desc, $specs) {
                $brandId = $this->resolveBrand($brandName);

                $stockQty = $inStock ? max($this->stock, 1) : 0;
                $showVal  = $inStock ? 1 : 0;

                if ($existing) {
                    $product = $existing;

                    $updateData = [
                        'quantity' => $stockQty,
                        'in_stock' => $inStock ? 1 : 0,
                        'show'     => $showVal,
                        'active'   => 1,
                    ];

                    if (!$existing->taxonomy_lock) {
                        $updateData['brand_id'] = $brandId;
                    } else {
                        Log::info("🏷️ Kontakt: taxonomy locked, brand უცვლელი — {$sku}");
                    }

                    $product->update($updateData);
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

                // ფასი
                ProductPrice::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'regular_price'    => $price,
                        'dealer_price'     => $price,
                        'discount_price'   => null,
                        'discount_percent' => 0,
                    ]
                );

                // translations
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

                // სურათი (მხოლოდ თუ არ აქვს)
                if (!empty($image) && empty($product->main_image)) {
                    $this->downloadImage($product, $image);
                }

                // სპეციფიკაციები — ძველი წავშალოთ, ახალი ჩავწეროთ
                if (!empty($specs)) {
                    $sectionIds = ProductFullSpecificationSection::where('product_id', $product->id)->pluck('id');
                    ProductFullSpecificationItem::whereIn('section_id', $sectionIds)->forceDelete();
                    ProductFullSpecificationSection::where('product_id', $product->id)->forceDelete();

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
                }

                Log::info("✅ Kontakt saved: {$sku} (brand={$brandId}, specs=" . count($specs) . ")");
            });

        } catch (Exception $e) {
            Log::error("❌ Kontakt job error [{$this->model}]: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * URL-დან /en/ /ka/ /ru/ მოშორება (ქართული default)
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        return preg_replace('#^(https?://[^/]+)/(en|ka|ru)(/|$)#i', '$1/', $url);
    }

    /**
     * ld+json ბლოკებიდან Product-ის ამოღება
     */
    private function extractProductJson(string $html): ?array
    {
        if (!preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $block) {
            $data = json_decode(trim($block), true);
            if (!is_array($data)) {
                continue;
            }

            // ერთი ობიექტი
            if (($data['@type'] ?? '') === 'Product') {
                return $data;
            }

            // @graph array
            if (isset($data['@graph']) && is_array($data['@graph'])) {
                foreach ($data['@graph'] as $item) {
                    if (($item['@type'] ?? '') === 'Product') {
                        return $item;
                    }
                }
            }
        }

        return null;
    }

    /**
     * .har__row → name/value
     */
    private function extractSpecs(string $html): array
    {
        $specs = [];

        try {
            $crawler = new Crawler($html);

            $crawler->filter('.har__row')->each(function (Crawler $row) use (&$specs) {
                $nameNode  = $row->filter('.har__title');
                $valueNode = $row->filter('.har__znach');

                if (!$nameNode->count() || !$valueNode->count()) {
                    return;
                }

                $name  = trim($nameNode->text());
                $value = trim($valueNode->text());

                // უსარგებლო value-ების გაფილტვრა
                if ($name === '' || $value === '' || $value === '-'
                    || mb_strpos($value, 'მიუწვდომელია') !== false) {
                    return;
                }

                $specs[] = ['name' => $name, 'value' => $value];
            });

        } catch (Exception $e) {
            Log::warning("⚠️ Kontakt specs parse: " . $e->getMessage());
        }

        return $specs;
    }

    private function cleanTitle(string $title): string
    {
        // " | Kontakt.ge" მოშორება
        $title = preg_replace('/\s*\|\s*Kontakt\.ge\s*$/i', '', $title);
        return trim($title);
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