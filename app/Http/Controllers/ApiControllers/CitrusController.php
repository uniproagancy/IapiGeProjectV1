<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Services\Products\CitrusProduct;
use App\Models\Product\Product;
use App\Models\Product\ProductFullSpecificationItem;
use App\Models\Product\ProductFullSpecificationSection;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductTranslation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class CitrusController extends Controller
{
    private const MAX_IMAGE_SIZE = 5 * 1024 * 1024;

    /**
     * GET /citrus/import
     *   ?product_url=https://citrus.ge/product/...
     *   &supplier_id=13
     *   &category_id=45
     *   &brand_id=12
     *   &sku_prefix=CITRUS
     */
    public function import(Request $request): JsonResponse
    {
        
        Log::info("Importing Citrus");
        $request->validate([
            'product_url' => 'required|url',
            'supplier_id' => 'required|integer',
            'category_id' => 'required|integer|exists:db_product_categories,id',
            'brand_id'    => 'required|integer|exists:db_product_brands,id',
            'sku_prefix'  => 'required|string|max:20',
        ]);

        try {
            $service     = new CitrusProduct();
            $productData = $service->getProduct($request->product_url);

            if (empty($productData) || empty($productData['id'])) {
                return response()->json(['success' => false, 'message' => 'პროდუქტი ვერ მოიძებნა'], 404);
            }

            $sku      = strtoupper(trim($request->sku_prefix)) . '-' . $productData['id'];
            $existing = Product::where('sku', $sku)->first();
            $isNew    = !$existing;

            $quantity = (int) ($productData['stock'] ?? 0);
            $inStock  = ($productData['stock_status'] ?? 0) > 0 ? 1 : 0;

            $regularPrice  = (float) ($productData['old_price'] ?? $productData['price'] ?? 0);
            $discountPrice = $productData['old_price'] ? (float) $productData['price'] : null;

            DB::transaction(function () use (
                $request, $productData, $sku, &$existing,
                $isNew, $quantity, $inStock, $regularPrice, $discountPrice
            ) {
                if ($isNew) {
                    $existing = Product::create([
                        'supplier_product_id' => $productData['id'],
                        'sku'                 => $sku,
                        'supplier_id'         => (int) $request->supplier_id,
                        'brand_id'            => (int) $request->brand_id,
                        'category_id'         => (int) $request->category_id,
                        'quantity'            => $quantity,
                        'in_stock'            => $inStock,
                        'show'                => $inStock,
                        'active'              => 1,
                        'main_image'          => null,
                        'update_lock'         => 0,
                        'taxonomy_lock'       => 0,
                    ]);

                    Log::info("➕ Citrus: ახალი", ['sku' => $sku, 'id' => $existing->id]);
                } else {
                    $existing->update([
                        'quantity' => $quantity,
                        'in_stock' => $inStock,
                        'show'     => $inStock,
                        'active'   => 1,
                    ]);

                    Log::info("🔄 Citrus: განახლდა", ['sku' => $sku, 'id' => $existing->id]);
                }

                // Translation
                ProductTranslation::updateOrCreate(
                    ['product_id' => $existing->id, 'locale' => 'ka'],
                    [
                        'title'       => $this->sanitize($productData['name']),
                        'slug'        => Str::slug($productData['name']) . '-' . $existing->id,
                        'description' => $this->sanitize($productData['details']['description'] ?? null),
                        'keywords'    => $this->sanitize($productData['seo']['meta_keywords'] ?? null),
                    ]
                );

                // Price
                ProductPrice::updateOrCreate(
                    ['product_id' => $existing->id],
                    [
                        'dealer_price'     => $regularPrice,
                        'regular_price'    => $regularPrice,
                        'discount_price'   => $discountPrice,
                        'discount_percent' => $discountPrice
                            ? (int) round((($regularPrice - $discountPrice) / $regularPrice) * 100)
                            : 0,
                    ]
                );

                // Full Specs — Alta-ს ზუსტი პატერნი
                $sectionIds = ProductFullSpecificationSection::where('product_id', $existing->id)->pluck('id');
                ProductFullSpecificationItem::whereIn('section_id', $sectionIds)->forceDelete();
                ProductFullSpecificationSection::where('product_id', $existing->id)->forceDelete();

                if (!empty($productData['attributes'])) {
                    $section = ProductFullSpecificationSection::create([
                        'product_id' => $existing->id,
                        'name'       => 'სრული მახასიათებლები',
                    ]);

                    foreach ($productData['attributes'] as $spec) {
                        $name = $this->sanitize($spec['name'] ?? null);
                        if (!$name) continue;

                        ProductFullSpecificationItem::create([
                            'section_id' => $section->id,
                            'name'       => $name,
                            'value'      => $this->sanitize($spec['value'] ?? null),
                            'filter'     => (!empty($spec['type']) && $spec['type'] === 'checkbox') ? 1 : 0,
                        ]);
                    }
                }

                // Images — მხოლოდ ახალ პროდუქტზე
                if ($isNew && !empty($productData['images'])) {
                    $this->downloadAndSaveImages($existing, $productData['images']);
                }
            });

            return response()->json([
                'success' => true,
                'message' => $isNew ? 'პროდუქტი დაემატა' : 'პროდუქტი განახლდა',
                'sku'     => $sku,
                'id'      => $existing->id,
            ]);

        } catch (Exception $e) {
            Log::error("❌ Citrus Import შეცდომა", [
                'url'   => $request->product_url,
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'შეცდომა: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function downloadAndSaveImages(Product $product, array $images): void
    {
        $gallery       = [];
        $mainImageSet  = false;
        $processedUrls = [];

        foreach ($images as $imageData) {
            $imageUrl = is_array($imageData) ? ($imageData['url'] ?? null) : $imageData;
            if (!$imageUrl || in_array($imageUrl, $processedUrls)) continue;
            $processedUrls[] = $imageUrl;

            try {
                $response = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->get($imageUrl);

                if (!$response->successful()) continue;
                if (strlen($response->body()) > self::MAX_IMAGE_SIZE) continue;

                $ext  = $this->getImageExtension($imageUrl);
                $path = "uploads/products/{$product->id}/" . Str::random(40) . ".{$ext}";
                Storage::disk('public')->put($path, $response->body());

                if (!$mainImageSet) {
                    $product->update(['main_image' => $path]);
                    $mainImageSet = true;
                } else {
                    $gallery[] = [
                        'product_id' => $product->id,
                        'path'       => $path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            } catch (Exception $e) {
                Log::warning("⚠️ Citrus: სურათი ვერ ჩამოიტვირთა", ['url' => $imageUrl, 'error' => $e->getMessage()]);
            }
        }

        if (!empty($gallery)) {
            ProductImage::insert($gallery);
        }
    }

    private function sanitize(?string $value): ?string
    {
        if (empty($value)) return null;
        $trimmed = trim($value);
        return !empty($trimmed) ? $trimmed : null;
    }

    private function getImageExtension(string $url): string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $ext : 'jpg';
    }
}