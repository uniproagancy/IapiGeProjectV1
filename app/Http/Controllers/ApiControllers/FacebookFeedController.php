<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use Illuminate\Http\Request;

class FacebookFeedController extends Controller
{
    //
    public function getFeed()
    {
        $products = Product::where('in_stock', 1)->get();
        $xml = new \SimpleXMLElement('<?xml version="1.0"?><rss xmlns:g="http://base.google.com/ns/1.0" version="2.0"></rss>');
        $channel = $xml->addChild('channel');
        $channel->addChild('title', 'Test Store');
        $channel->addChild('link', 'http://www.example.com');
        $channel->addChild('description', 'An example store');
        foreach($products as $product) {
            if($product->in_stock == 1) {
                $stock = 'in stock';
            } else {
                $stock = 'out of stock';
            }

            if(!empty($product->price->discount_price)) {
                $price = $product->price->discount_price;
            } else {
                $price = $product->price->regular_price;
            }

            $item = $channel->addChild('item');
            $item->addChild('g:id', $product->id);
            $item->addChild('g:title', $product->translation('ka')->title);
            $item->addChild('g:description', $this->sanitize($product->translation('ka')->description));
            $item->addChild('g:link', route('web.products.view', $product->translation('ka')->slug));
            $item->addChild('g:image_link', url($product->main_image));
            $item->addChild('g:brand', $product->brand->translation('ka')->title);
            $item->addChild('g:condition', 'new');
            $item->addChild('g:availability', $stock);
//            $item->addChild('g:google_product_category', $product->id);
            $item->addChild('g:price', $price);
        }
        return response($xml->asXML(), 200)
            ->header('Content-Type', 'application/xml');
    }

    public static function sanitize($text)
    {
        if (!$text) {
            return '';
        }
        $text = html_entity_decode($text, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
        $text = strip_tags($text);
        $replacements = [
            '&nbsp;'  => ' ',
            '&amp;'   => '&',
            '&quot;'  => '"',
            '&apos;'  => "'",
            '&lt;'    => '<',
            '&gt;'    => '>',
            '&ldquo;' => '"',
            '&rdquo;' => '"',
            '&lsquo;' => "'",
            '&rsquo;' => "'",
            '&ndash;' => '-',
            '&mdash;' => '-',
            '&hellip;' => '...',
            '&copy;'  => '©',
            '&reg;'   => '®',
            '&trade;' => '™',
            '&euro;'  => '€',
            '&pound;' => '£',
            '&yen;'   => '¥',
            '<br>'    => ' ',
            '<br/>'   => ' ',
            '<br />'  => ' ',
            '\n'      => ' ',
            '\r'      => ' ',
            '\t'      => ' ',
        ];
        foreach ($replacements as $entity => $replacement) {
            $text = str_ireplace($entity, $replacement, $text);
        }
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        $text = mb_substr($text, 0, 5000);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        $text = htmlspecialchars($text, ENT_XML1, 'UTF-8');
        return $text;
    }
}
