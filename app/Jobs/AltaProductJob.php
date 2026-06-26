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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class AltaProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries         = 3;
    public int $timeout       = 300;
    public int $maxExceptions = 3;

    protected array $productData;
    protected array $availability;
    protected int   $altaQuantity;

    public function __construct(array $data, int $altaQuantity = 5)
    {
        $this->productData  = $data['product'];
        $this->availability = $data['availability'] ?? [];
        $this->altaQuantity = $altaQuantity;
    }

    public function handle(): void
    {
        $product = $this->productData;
        $barCode = $product['barCode'] ?? null;
        $name    = $product['name'] ?? 'Unknown';
        $altaId  = $product['id'] ?? null;

        Log::info("🔄 AltaJob: processing | barCode={$barCode} | name={$name}");

        if (empty($barCode)) {
            Log::warning("⚠️ AltaJob: barCode empty, skip | alta_id={$altaId}");
            return;
        }

        $sku = 'ALTA-' . $barCode;

        try {
            DB::transaction(function () use ($product, $barCode, $sku, $name) {
                $existing = Product::where('sku', $sku)
                    ->where('supplier_id', 2)
                    ->first();

                $price          = (float) ($product['price'] ?? 0);
                $previousPrice  = (float) ($product['previousPrice'] ?? 0);
                $hasDiscount    = $previousPrice > 0 && $previousPrice > $price;
                $regularPrice   = $hasDiscount ? $previousPrice : $price;
                $discountPrice  = $hasDiscount ? $price : null;
                $discountPct    = (int) ($product['discountPercent'] ?? 0);

                $inStock  = ($product['storageQuantity'] ?? 0) > 0 ? 1 : 0;
                $quantity = $this->altaQuantity > 0 ? $this->altaQuantity : ($inStock ? 5 : 0);

                if ($existing) {
                    if ($existing->update_lock) {
                        Log::info("🔒 AltaJob: locked, skip | sku={$sku}");
                        return;
                    }

                    // ფასი + სტოკი განახლება
                    ProductPrice::updateOrCreate(
                        ['product_id' => $existing->id],
                        [
                            'dealer_price'     => $regularPrice,
                            'regular_price'    => $regularPrice,
                            'discount_price'   => $discountPrice,
                            'discount_percent' => $discountPct,
                        ]
                    );

                    $updateData = [
                        'quantity' => $quantity,
                        'in_stock' => $inStock,
                        'show'     => $inStock,
                        'active'   => 1,
                    ];

                    if (!$existing->taxonomy_lock) {
                        $updateData['brand_id']    = $this->getBrandId($product);
                        $updateData['category_id'] = $this->getCategoryId($product);
                    }

                    $existing->update($updateData);

                    Log::info("🔄 AltaJob: განახლდა | sku={$sku} | price={$regularPrice}");
                } else {
                    // ახალი პროდუქტი
                    $brandId    = $this->getBrandId($product);
                    $categoryId = $this->getCategoryId($product);

                    $newProduct = Product::create([
                        'sku'                 => $sku,
                        'supplier_id'         => 2,
                        'supplier_product_id' => (string) ($product['id'] ?? ''),
                        'brand_id'            => $brandId,
                        'category_id'         => $categoryId,
                        'quantity'            => $quantity,
                        'in_stock'            => $inStock,
                        'show'                => $inStock,
                        'active'              => 1,
                        'main_image'          => null,
                    ]);

                    // ფასი
                    ProductPrice::create([
                        'product_id'       => $newProduct->id,
                        'dealer_price'     => $regularPrice,
                        'regular_price'    => $regularPrice,
                        'discount_price'   => $discountPrice,
                        'discount_percent' => $discountPct,
                    ]);

                    // Translation
                    $baseSlug = Str::slug($name) . '-' . $newProduct->id;
                    foreach (['ka', 'en'] as $locale) {
                        ProductTranslation::create([
                            'product_id'  => $newProduct->id,
                            'locale'      => $locale,
                            'title'       => $name,
                            'slug'        => $baseSlug . ($locale === 'en' ? '-en' : ''),
                            'description' => $product['description'] ?? null,
                        ]);
                    }

                    // Specs
                    $this->createSpecs($newProduct, $product);

                    // სურათი
                    $this->downloadImage($newProduct, $product['imageUrl'] ?? ($product['images'][0] ?? null));

                    Log::info("✨ AltaJob: ახალი პროდუქტი | sku={$sku} | id={$newProduct->id}");
                }
            });
        } catch (Exception $e) {
            Log::error("❌ AltaJob error sku={$sku}: " . $e->getMessage());
            throw $e;
        }
    }

    private function getBrandId(array $product): int
    {
        try {
            // specificationGroup-დან ბრენდი
            $brandName = null;
            foreach ($product['specificationGroup'] ?? [] as $group) {
                foreach ($group['specifications'] ?? [] as $spec) {
                    if (in_array($spec['specificationName'], ['ბრენდი', 'Brand', 'Бренд'])) {
                        $brandName = $spec['specificationMeaning'] ?? null;
                        break 2;
                    }
                }
            }

            if (empty($brandName)) {
                // mainSpecification-დან
                foreach ($product['mainSpecification'] ?? [] as $spec) {
                    if (in_array($spec['specificationName'], ['ბრენდი', 'Brand'])) {
                        $brandName = $spec['specificationMeaning'] ?? null;
                        break;
                    }
                }
            }

            if (empty($brandName)) return 1;

            $normalized = mb_strtolower(trim($brandName));
            $brand = ProductBrand::whereHas('translations',
                fn($q) => $q->whereRaw('LOWER(TRIM(title)) = ?', [$normalized])
            )->first();

            if ($brand) return $brand->id;

            // ახალი ბრენდი
            $newBrand = ProductBrand::create(['active' => 1, 'show' => 1]);
            foreach (['ka', 'en'] as $locale) {
                ProductBrandTranslation::create([
                    'product_brand_id' => $newBrand->id,
                    'locale'           => $locale,
                    'title'            => $brandName,
                    'slug'             => Str::slug($brandName) . '-' . $newBrand->id . ($locale === 'en' ? '-en' : ''),
                ]);
            }
            Log::info("✨ Alta: ახალი ბრენდი '{$brandName}' id={$newBrand->id}");
            return $newBrand->id;

        } catch (Exception $e) {
            return 1;
        }
    }

    private function getCategoryId(array $product): int
    {
        try {
            $categoryName = $product['categoryName'] ?? null;
            if (empty($categoryName)) return 4;

            $cat = ProductCategory::whereRaw(
                'LOWER(TRIM(alta_category_name)) = ?',
                [mb_strtolower(trim($categoryName))]
            )->first();

            if ($cat) {
                Log::info("✅ Alta category mapped: '{$categoryName}' → id={$cat->id}");
                return $cat->id;
            }

            Log::warning("⚠️ Alta category not mapped: '{$categoryName}'");
            return 4;
        } catch (Exception $e) {
            return 4;
        }
    }

    private function createSpecs(Product $product, array $data): void
    {
        // Full specs
        foreach ($data['specificationGroup'] ?? [] as $group) {
            if (empty($group['groupName'])) continue;
            $section = ProductFullSpecificationSection::create([
                'product_id' => $product->id,
                'name'       => $group['groupName'],
            ]);
            foreach ($group['specifications'] ?? [] as $spec) {
                if (empty($spec['specificationName'])) continue;
                ProductFullSpecificationItem::create([
                    'section_id' => $section->id,
                    'name'       => $spec['specificationName'],
                    'value'      => $spec['specificationMeaning'] ?? null,
                    'filter'     => 0,
                ]);
            }
        }

        // Short specs (mainSpecification)
        $shorts = [];
        foreach ($data['mainSpecification'] ?? [] as $spec) {
            if (empty($spec['specificationName'])) continue;
            $shorts[] = [
                'product_id' => $product->id,
                'name'       => mb_substr($spec['specificationName'] ?? '', 0, 255),
                'value'      => mb_substr($spec['specificationMeaning'] ?? '', 0, 255),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if (!empty($shorts)) {
            ProductShortSpecification::insert($shorts);
        }
    }

    private function downloadImage(Product $product, ?string $imageUrl): void
    {
        if (empty($imageUrl)) return;

        try {
            // სერვერზე API-ს გამოძახება სურათის ასატვირთად
            $serverUrl = env('APP_SERVER_URL', 'https://iapi.ge');
            $secret    = env('UPLOAD_SECRET', 'alta_upload_secret_2026');

            $response = Http::timeout(30)->post("{$serverUrl}/api/upload/image", [
                'product_id' => $product->id,
                'image_url'  => $imageUrl,
                'secret'     => $secret,
            ]);

            if ($response->successful() && $response->json('success')) {
                Log::info("📸 Alta: სურათი სერვერზე შენახულია | id={$product->id}");
            } else {
                Log::warning("⚠️ Alta: სურათი ვერ შეინახა | " . $response->body());
            }
        } catch (Exception $e) {
            Log::warning("⚠️ Alta: სურათის upload შეცდომა | " . $e->getMessage());
        }
    }
}