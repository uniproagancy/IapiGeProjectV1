<?php

namespace App\Jobs;

use App\Models\Brand;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductGallery;
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
use Exception;

class AltaProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    protected array $productData;
    protected int   $quantity;

    public function __construct(array $productData, int $quantity)
    {
        $this->productData = $productData;
        $this->quantity    = $quantity;
    }

    public function handle(): void
    {
        $product      = $this->productData['product']      ?? null;
        $availability = $this->productData['availability'] ?? [];

        if (!$product) {
            Log::warning("⚠️ AltaJob: product data ცარიელია");
            return;
        }

        $barCode  = $product['barCode']  ?? null;
        $name     = $product['name']     ?? null;
        $imageUrl = $product['coverUrl'] ?? null;

        if (!$barCode || !$name) {
            Log::warning("⚠️ AltaJob: barCode ან name არ არის");
            return;
        }

        Log::info("🔄 AltaJob: processing | barCode={$barCode} | name={$name}");

        // Brand
        $brandName = $product['brandName'] ?? null;
        $brandId   = null;
        if ($brandName) {
            $brand = Brand::firstOrCreate(
                ['name' => $brandName],
                ['status' => 1]
            );
            if ($brand->wasRecentlyCreated) {
                Log::info("✨ Alta: ახალი ბრენდი '{$brandName}' id={$brand->id}");
            }
            $brandId = $brand->id;
        }

        // Category
        $categoryId   = null;
        $categoryName = $product['categoryName'] ?? null;
        if ($categoryName) {
            $cat = ProductCategory::whereRaw(
                'LOWER(TRIM(alta_category_name)) = ?',
                [mb_strtolower(trim($categoryName))]
            )->first();

            if ($cat) {
                $categoryId = $cat->id;
                Log::info("✅ Alta category mapped: '{$categoryName}' → id={$categoryId}");
            } else {
                Log::warning("⚠️ Alta category not mapped: '{$categoryName}'");
            }
        }

        // Price
        $originalPrice   = (float) ($product['rrp']['original']   ?? 0);
        $discountedPrice = (float) ($product['rrp']['discounted']  ?? 0);
        $finalPrice      = $discountedPrice > 0 ? $discountedPrice : $originalPrice;

        // SKU
        $sku = 'ALTA-' . $barCode;

        // Product upsert
        $dbProduct = Product::updateOrCreate(
            ['sku' => $sku],
            [
                'supplier_id'    => 2,
                'brand_id'       => $brandId,
                'category_id'    => $categoryId,
                'status'         => 1,
                'quantity'       => $this->quantity,
                'original_price' => $originalPrice,
                'price'          => $finalPrice,
            ]
        );

        if ($dbProduct->wasRecentlyCreated) {
            Log::info("✨ AltaJob: ახალი პროდუქტი | sku={$sku} | id={$dbProduct->id}");
        } else {
            Log::info("🔁 AltaJob: განახლდა | sku={$sku} | id={$dbProduct->id}");
        }

        // Translation
        ProductTranslation::updateOrCreate(
            ['product_id' => $dbProduct->id, 'locale' => 'ka'],
            ['name' => $name, 'description' => $product['description'] ?? null]
        );

        // Price history
        ProductPrice::create([
            'product_id' => $dbProduct->id,
            'price'      => $finalPrice,
        ]);

        // სურათი — Worker-ით
        $this->downloadImage($dbProduct, $imageUrl);
    }

    private function downloadImage(Product $product, ?string $imageUrl): void
    {
        if (empty($imageUrl)) return;

        try {
            Log::info("🖼️ Alta image URL: {$imageUrl}");

            // Worker-ით ჩამოვტვირთოთ სურათი (hotlink protection bypass)
            $workerUrl = env('ALTA_WORKER_URL', 'https://dry-king-29d3.royal-sunset-e1c6.workers.dev')
                . '?' . http_build_query([
                    'type' => 'image',
                    'url'  => $imageUrl,
                ]);

            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get($workerUrl);

            if (!$response->successful()) {
                Log::warning("⚠️ Alta: სურათი ვერ ჩამოიტვირთა HTTP=" . $response->status() . " | {$imageUrl}");
                return;
            }

            $ext  = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg');
            $ext  = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) ? $ext : 'jpg';
            $path = "uploads/products/{$product->id}/main_{$product->id}.{$ext}";

            Storage::disk('public')->put($path, $response->body());
            $product->update(['main_image' => $path]);

            Log::info("📸 Alta: სურათი შენახულია | id={$product->id} | path={$path}");

        } catch (Exception $e) {
            Log::warning("⚠️ Alta: სურათი ვერ ჩამოიტვირთა | " . $e->getMessage());
        }
    }
}