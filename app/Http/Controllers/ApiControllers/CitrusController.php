<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Services\Products\CitrusProduct;
use Illuminate\Http\Request;

use App\Models\Product\Product;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductTranslation;
use App\Models\Product\ProductFullSpecificationSection;
use App\Models\Product\ProductFullSpecificationItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class CitrusController extends Controller
{
    //
    protected function getImageExtension(string $url): string
    {
        try {
            $parsed = parse_url($url);
            $path = $parsed['path'] ?? '';
            $ext = pathinfo($path, PATHINFO_EXTENSION);

            $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            return in_array(strtolower($ext), $validExtensions) ? strtolower($ext) : 'jpg';

        } catch (Exception $e) {
            Log::warning("⚠️  Error getting image extension: {$e->getMessage()}");
            return 'jpg';
        }
    }

    public function get(Request $request)
    {
        $service = new CitrusProduct();
        $productData = $service->getProduct($request->product_url);

        $check_product = Product::where('supplier_product_id', $productData['id'])->first();
        if($productData['stock'] > 0 && $productData['stock_status'] === 1) {
            $quantity = $productData['stock'];
            $in_stock = $productData['stock_status'];
        }

        if($check_product) {
            $check_product->update([
                'show' => $in_stock ? $in_stock : 0,
                'active' => $in_stock ? $in_stock : 0,
                'quantity' => $quantity ? $quantity : 0,
            ]);
            $productPrice = $productData['old_price'] ?? $productData['price'] ?? 0;
            $discountPrice = $productData['old_price'] ? $productData['price'] : null;
            $check_product->price()->update([
                'dealer_price' => $productPrice,
                'regular_price' => $productPrice,
                'discount_price' => $discountPrice,
                'discount_percent' => $productData['discountPercent'] ?? 0,
            ]);
        } else {
            $product = Product::create([
                'supplier_product_id' => $productData['id'],
                'brand_id' => 114,
                'category_id' => 39,
                'sku' => 'GL-'.$productData['id'],
                'supplier_id' => 5,
                'main_image' => 1,
                'quantity' => $quantity ? $quantity : 0,
                'in_stock' => $in_stock ? $in_stock : 0,
                'show' => $in_stock ? $in_stock : 0,
                'active' => $in_stock ? $in_stock : 0,
            ]);
            ProductTranslation::create([
                'product_id' => $product->id,
                'locale' => 'ka',
                'title' => $productData['name'],
                'slug' => Str::slug($productData['name']) . "-{$product->id}",
                'description' => $productData['details']['description'] ? $productData['details']['description'] : $productData['seo']['description'],
                'keywords' => $productData['seo']['meta_keywords'],
            ]);
            $productPrice = $productData['old_price'] ?? $productData['price'] ?? 0;
            $discountPrice = $productData['old_price'] ? $productData['price'] : null;
            ProductPrice::create([
                'product_id' => $product->id,
                'dealer_price' => $productPrice,
                'regular_price' => $productPrice,
                'discount_price' => $discountPrice,
                'discount_percent' => $productData['discountPercent'] ?? 0,
            ]);
            if (empty($productData['images'])) {
                return;
            }
            $images = [];
            foreach ($productData['images'] as $index => $imageUrl) {
                $response = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->get($imageUrl['url']);
                if (!$response->successful()) {
                    Log::warning("⚠️  Failed to download image: {$imageUrl['url']}");
                    continue;
                }
                $ext = $this->getImageExtension($imageUrl['url']);
                $filename = Str::random(40) . '.' . $ext;
                $path = "uploads/products/{$product->id}/{$filename}";
                Storage::disk('public')->put($path, $response->body());
                if ($index === 0) {
                    $product->update(['main_image' => $path]);
                } else {
                    $images[] = [
                        'product_id' => $product->id,
                        'path' => $path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            if (!empty($images)) {
                ProductImage::insert($images);
            }

            try {
                if (empty($productData['attributes'])) {
                    return;
                }
                $section = ProductFullSpecificationSection::create([
                    'product_id' => $product->id,
                    'name' => 'სრული მახასიათებლები11',
                ]);

                if (!empty($productData['attributes'])) {
                    foreach ($productData['attributes'] as $spec) {
                        if (empty($spec['name'])) {
                            continue;
                        }
                        ProductFullSpecificationItem::create([
                            'section_id' => $section->id,
                            'name' => $spec['name'],
                            'value' => $this->sanitizeString($spec['value']),
                            'filter' => !empty($spec['type']) && $spec['type'] === 'checkbox' ? 1 : 0,
                        ]);
                    }
                }
            } catch (Exception $e) {
                throw $e;
            }
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
