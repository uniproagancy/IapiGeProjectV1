<?php

namespace App\Http\Controllers;

use App\Models\Product\Product;
use Illuminate\Http\Request;
use MeeeetDev\LaravelFacebookCatalog\LaravelFacebookCatalog;

class FacebookFeedController extends Controller
{
    public function __construct()
    {
    }
    public function getFeed() {
        ob_end_clean();
        ob_start();
        LaravelFacebookCatalog::setTitle('Example feed');
        LaravelFacebookCatalog::setDescription('Example feed of the Example shop');
        LaravelFacebookCatalog::setLink('https://example.shop');
        LaravelFacebookCatalog::setCurrency('GEL');
        $products = Product::where('active', 1)
            ->where('in_stock', 1)
            ->where('show', 1)
            ->limit(100)
            ->get();
        foreach($products as $product) {
            if($product->main_image != 1) {
                $product_image = $product->main_image;
            }
            elseif(!empty($product->images[0]->path)) {
                $product_image = $product->images[0]->path;
            } else{
                $product_image = url('web-assets/img/no-product.png');
            }

            if(!empty($product->price->discount_price)) {
                $product_price = $product->price->discount_price;
            } else {
                $product_price = $product->price->regular_price;
            }
            LaravelFacebookCatalog::addItem([
                'link' => route('web.products.view', $product->translations->where('locale', app()->getLocale())->first()->slug ?? $product->translations->where('locale', 'ka')->first()->slug),
                'id' => $product->id,
                'title' => $product->translations->where('locale', app()->getLocale())->first()->title ?? $product->translations->where('locale', 'ka')->first()->title,
                'image_link' => url('storage/'.$product_image),
                'description' => htmlspecialchars($product->translations->where('locale', 'ka')->first()->description),
                'availability' => 'in stock',
                "price" => $product_price,
                'brand' => htmlspecialchars($product->brand->translations->where('locale', 'ka')->first()->title),
                'google_product_category' => $product->category->parent->google_category_id,
                'condition' => 'new',
            ]);
        }
        return LaravelFacebookCatalog::display();
    }
}
