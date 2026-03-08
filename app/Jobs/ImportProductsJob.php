<?php

namespace App\Jobs\Product;

use App\Models\Product\Product;
use App\Models\Product\ProductImage;
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
use Exception;
use Throwable;

class ImportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;   // 1 საათი
    public int $tries   = 1;

    public function __construct(
        private readonly array $products,
        private readonly int   $categoryId,
        private readonly int   $brandId,
        private readonly int   $supplierId,
    ) {}

    public function handle(): void
    {
        $total   = count($this->products);
        $created = 0;
        $updated = 0;
        $failed  = 0;

        Log::info("🚀 Import დაიწყო: {$total} პროდუქტი");

        foreach ($this->products as $index => $data) {
            try {
                $this->importProduct($data) === 'created'
                    ? $created++
                    : $updated++;

                Log::info("✅ [{$index}/{$total}] external_id={$data['external_id']} — {$data['title_ka']}");

            } catch (Throwable $e) {
                $failed++;
                Log::error("❌ [{$index}/{$total}] external_id={$data['external_id']} — {$e->getMessage()}");
            }
        }

        Log::info("🏁 Import დასრულდა — created:{$created}, updated:{$updated}, failed:{$failed}");
    }

    // ============================================
    // ერთი პროდუქტის შენახვა/განახლება
    // ============================================

    private function importProduct(array $data): string
    {
        $externalId = (int) $data['external_id'];
        $isNew      = false;

        DB::transaction(function () use ($data, $externalId, &$isNew) {

            // ── 1. პროდუქტი: შექმნა ან განახლება ──────────────────────────────
            $sku = 'COMFO-' . $externalId;

            $product = Product::where('sku', $sku)->first();

            $quantity = is_numeric($data['quantity'])
                ? (int) $data['quantity']
                : 0; // "50+" → 0, in_stock=1 ჩავრთავთ ქვემოთ

            $inStock = ($data['quantity'] === '50+' || (int) ($data['quantity'] ?? 0) > 0) ? 1 : 0;

            if (!$product) {
                $product = Product::create([
                    'category_id' => $this->categoryId,
                    'brand_id'    => $this->brandId,
                    'supplier_id' => $this->supplierId,
                    'sku'         => $sku,
                    'quantity'    => $quantity,
                    'active'      => 1,
                    'in_stock'    => $inStock,
                    'preorder'    => 0,
                    'main_image'  => null,
                    'show'        => 1,
                ]);
                $isNew = true;
            } else {
                $product->update([
                    'quantity' => $quantity,
                    'in_stock' => $inStock,
                ]);
            }

            // ── 2. ფასი ───────────────────────────────────────────────────────
            $priceData = [
                'dealer_price'   => 0,
                'regular_price'  => (float) ($data['regular_price'] ?? 0),
                'discount_price' => !empty($data['discount_price'])
                    ? (float) $data['discount_price']
                    : null,
            ];

            ProductPrice::updateOrCreate(
                ['product_id' => $product->id],
                $priceData
            );

            // ── 3. თარგმანი (ka) ──────────────────────────────────────────────
            if (!empty($data['title_ka'])) {
                $slug = Str::slug($data['title_ka']) . '-' . $product->id;

                ProductTranslation::updateOrCreate(
                    ['product_id' => $product->id, 'locale' => 'ka'],
                    [
                        'title'       => $data['title_ka'],
                        'slug'        => $slug,
                        'description' => null,
                        'keywords'    => null,
                    ]
                );
            }

            // ── 4. მთავარი სურათი ─────────────────────────────────────────────
            if (!empty($data['main_image'])) {
                // განახლებისას მხოლოდ main_image რომ არ ჰქონდა
                if (empty($product->main_image)) {
                    $mainPath = $this->downloadImage(
                        $data['main_image'],
                        'uploads/products/' . $product->id
                    );
                    if ($mainPath) {
                        $product->update(['main_image' => $mainPath]);
                    }
                }
            }

            // ── 5. დამატებითი სურათები ────────────────────────────────────────
            if (!empty($data['additional_images']) && is_array($data['additional_images'])) {
                // განახლებისას არ გამოვტოვოთ უკვე შენახული სურათები
                $existingCount = ProductImage::where('product_id', $product->id)->count();

                if ($existingCount === 0) {
                    foreach ($data['additional_images'] as $imgUrl) {
                        $path = $this->downloadImage(
                            $imgUrl,
                            'uploads/products/' . $product->id
                        );
                        if ($path) {
                            ProductImage::create([
                                'product_id' => $product->id,
                                'path'       => $path,
                            ]);
                        }
                    }
                }
            }
        });

        return $isNew ? 'created' : 'updated';
    }

    // ============================================
    // სურათის გადმოწერა და შენახვა
    // ============================================

    private function downloadImage(string $url, string $folder): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                Log::warning("⚠️ სურათი ვერ გადმოიწერა: {$url}");
                return null;
            }

            // ფაილის სახელი URL-იდან
            $originalName = basename(parse_url($url, PHP_URL_PATH));
            $extension    = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'jpg';
            $filename     = Str::uuid() . '.' . $extension;
            $path         = $folder . '/' . $filename;

            Storage::disk('public')->put($path, $response->body());

            return $path;

        } catch (Exception $e) {
            Log::warning("⚠️ სურათის შენახვის შეცდომა ({$url}): " . $e->getMessage());
            return null;
        }
    }
}