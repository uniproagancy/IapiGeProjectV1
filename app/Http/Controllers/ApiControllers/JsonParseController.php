<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductTranslation;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductCategoryTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JsonParseController extends Controller
{
    public function loadJson()
    {
        $jsonPath = storage_path('app/Json2.json');
        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        foreach ($data as $item) {
            $product = Product::where('sku', 'COMFO-'.$item['external_id'])->first();

            if (!$product) {
                Log::warning('Product not found: COMFO-' . $item['external_id']);
                continue;
            }

            $quantity = (int) filter_var($item['quantity'], FILTER_SANITIZE_NUMBER_INT);
            $show = $quantity > 2 ? 1 : 0;

            $product->update([
                'quantity' => $quantity,
                'show'     => $show,
            ]);

            $discount_price = (!empty($item['discount_price']) && $item['discount_price'] > 0)
                ? $item['discount_price']
                : null;

            ProductPrice::where('product_id', $product->id)->update([
                'regular_price'  => $item['regular_price'] + 20,
                'discount_price' => $discount_price + 20,
            ]);
        }
    }
}