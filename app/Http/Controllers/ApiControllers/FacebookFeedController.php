<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use MeeeetDev\LaravelFacebookCatalog\LaravelFacebookCatalog;


class FacebookFeedController extends Controller
{

    public function getFeed() {
        LaravelFacebookCatalog::setTitle('Example feed');
        LaravelFacebookCatalog::setDescription('Example feed of the Example shop');
        LaravelFacebookCatalog::setLink('https://example.shop');
        LaravelFacebookCatalog::setCurrency('USD');

        $products = Product::where('active', 1)
            ->where('in_stock', 1)
            ->where('show', 1)
            ->limit(20)
            ->get();

        foreach($products as $product) {
            LaravelFacebookCatalog::addItem([
                'link' => 'https://example.shop/p/foo-bar',
                'id' => 'SKU123',
                'title' => 'Foo bar',
                'image_link' => 'https://example.shop/images/foo-bar.png',
                'description' => 'Foo bar best product',
                'availability' => 'in stock',
                "price" => 99.99,
                'brand' => 'Foo brand',
                'condition' => 'new',
            ]);
        }
        return LaravelFacebookCatalog::display();
    }

}