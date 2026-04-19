<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Services\Products\UshopProduct;
use App\Models\Product\Product;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductTranslation;
use App\Models\Product\ProductFullSpecificationSection;
use App\Models\Product\ProductFullSpecificationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class UshopController extends Controller
{
    protected function getImageExtension(string $url): string
    {
        try {
            $parsed = parse_url($url);
            $path   = $parsed['path'] ?? '';
            $ext    = pathinfo($path, PATHINFO_EXTENSION);

            $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            return in_array(strtolower($ext), $validExtensions)
                ? strtolower($ext)
                : 'jpg';
        } catch (Exception $e) {
            Log::warning("⚠️ Error getting image extension: {$e->getMessage()}");
            return 'jpg';
        }
    }

    public function import(Request $request)
    {
        $service  = new UshopProduct();
        $products = $service->getProducts();

        if (empty($products)) {
            return response()->json(['message' => 'No products found'], 200);
        }

        $imported = 0;
        $updated  = 0;

        foreach ($products as $productData) {

            try {
                // stock / in_stock
                $quantity = max(0, (int) ($productData['stock'] ?? 0));
                $in_stock = $quantity > 0 ? 1 : 0;

                // prices
                $regularPrice  = (float) ($productData['regular_price'] ?? 0);
                $salePrice     = !empty($productData['sale_price'])
                    ? (float) $productData['sale_price']
                    : null;

                $existingProduct = Product::where('supplier_product_id', $productData['ID'])
                    ->where('supplier_id', $request->supplier_id ?? 5)
                    ->first();

                // ── UPDATE ────────────────────────────────────────────────
                if ($existingProduct) {
                    $existingProduct->update([
                        'quantity' => $quantity,
                        'in_stock' => $in_stock,
                        'show'     => $in_stock,
                    ]);

                    $existingProduct->price()->update([
                        'dealer_price'       => $regularPrice,
                        'regular_price'      => $regularPrice,
                        'discount_price'     => $salePrice,
                        'discount_percent'   => 0,
                    ]);

                    $updated++;
                    continue;
                }

                // ── CREATE ────────────────────────────────────────────────
                $product = Product::create([
                    'supplier_product_id' => $productData['ID'],
                    'brand_id'            => 1,
                    'category_id'         => 182,
                    'sku'                 => 'USHOP-' . $productData['ID'],
                    'supplier_id'         => $request->supplier_id ?? 5,
                    'main_image'          => null,
                    'quantity'            => $quantity,
                    'in_stock'            => $in_stock,
                    'show'                => $in_stock,
                    'active'              => 1,
                ]);

                // translation
                $title = $productData['post_title'] ?? 'პროდუქტი ' . $productData['ID'];
                ProductTranslation::create([
                    'product_id'  => $product->id,
                    'locale'      => 'ka',
                    'title'       => $title,
                    'slug'        => Str::slug($title) . '-' . $product->id,
                    'description' => $productData['post_content'] ?? '',
                    'keywords'    => '',
                ]);

                // price
                ProductPrice::create([
                    'product_id'       => $product->id,
                    'dealer_price'     => $regularPrice,
                    'regular_price'    => $regularPrice,
                    'discount_price'   => $salePrice,
                    'discount_percent' => 0,
                ]);

                // ── IMAGES ────────────────────────────────────────────────
                $allImagePaths = $this->collectImageUrls($productData);

                if (!empty($allImagePaths)) {
                    $this->downloadAndSaveImages($product, $allImagePaths);
                }

                // ── SPECIFICATIONS ────────────────────────────────────────
                $this->saveSpecifications($product, $productData);

                $imported++;

            } catch (Exception $e) {
                Log::error("UshopController: product ID {$productData['ID']} failed — {$e->getMessage()}");
                continue;
            }
        }

        return response()->json([
            'imported' => $imported,
            'updated'  => $updated,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Featured image + gallery images ერთ array-ში
     */
    private function collectImageUrls(array $productData): array
    {
        $baseUrl = 'https://ushop.ge/wp-content/uploads/';
        $urls    = [];

        if (!empty($productData['featured_image_path'])) {
            $urls[] = $baseUrl . $productData['featured_image_path'];
        }

        if (!empty($productData['gallery_images'])) {
            foreach (explode(',', $productData['gallery_images']) as $path) {
                $path = trim($path);
                if ($path) {
                    $urls[] = $baseUrl . $path;
                }
            }
        }

        return array_unique($urls);
    }

    private function downloadAndSaveImages(Product $product, array $imageUrls): void
    {
        $extraImages = [];

        foreach ($imageUrls as $index => $imageUrl) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->get($imageUrl);

                if (!$response->successful()) {
                    Log::warning("⚠️ Failed to download image: {$imageUrl}");
                    continue;
                }

                $ext      = $this->getImageExtension($imageUrl);
                $filename = Str::random(40) . '.' . $ext;
                $path     = "uploads/products/{$product->id}/{$filename}";

                Storage::disk('public')->put($path, $response->body());

                if ($index === 0) {
                    $product->update(['main_image' => $path]);
                } else {
                    $extraImages[] = [
                        'product_id' => $product->id,
                        'path'       => $path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            } catch (Exception $e) {
                Log::warning("⚠️ Image download exception: {$e->getMessage()}");
                continue;
            }
        }

        if (!empty($extraImages)) {
            ProductImage::insert($extraImages);
        }
    }

    private function saveSpecifications(Product $product, array $productData): void
    {
        // ushop-ის მხრიდან categories / brand specs-ის სახით შეიძლება შეინახო
        $specs = [];

        if (!empty($productData['categories'])) {
            $specs[] = ['name' => 'კატეგორია', 'value' => $productData['categories']];
        }
        if (!empty($productData['brand'])) {
            $specs[] = ['name' => 'ბრენდი', 'value' => $productData['brand']];
        }
        if (!empty($productData['sku'])) {
            $specs[] = ['name' => 'SKU', 'value' => $productData['sku']];
        }

        if (empty($specs)) {
            return;
        }

        $section = ProductFullSpecificationSection::create([
            'product_id' => $product->id,
            'name'       => 'სრული მახასიათებლები',
        ]);

        foreach ($specs as $spec) {
            if (empty($spec['name'])) {
                continue;
            }
            ProductFullSpecificationItem::create([
                'section_id' => $section->id,
                'name'       => $spec['name'],
                'value'      => $this->sanitizeString($spec['value']),
                'filter'     => 0,
            ]);
        }
    }

    private function sanitizeString(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $trimmed = trim($value);
        return !empty($trimmed) ? $trimmed : null;
    }
}