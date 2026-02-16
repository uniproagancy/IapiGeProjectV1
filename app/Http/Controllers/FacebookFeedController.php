<?php

namespace App\Http\Controllers;

use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MeeeetDev\LaravelFacebookCatalog\LaravelFacebookCatalog;

class FacebookFeedController extends Controller
{
    public function getFeed()
    {
        Log::info('Facebook Feed Generation Started');

        // Clear any existing output buffers
        if (ob_get_level()) {
            Log::info('Output buffer cleared, level: ' . ob_get_level());
            ob_end_clean();
        }
        ob_start();

        // Set feed metadata
        LaravelFacebookCatalog::setTitle('Example feed');
        LaravelFacebookCatalog::setDescription('Example feed of the Example shop');
        LaravelFacebookCatalog::setLink('https://example.shop');
        LaravelFacebookCatalog::setCurrency('GEL');
        Log::info('Feed metadata set');

        // Get products
        $products = Product::where('active', 1)
            ->where('in_stock', 1)
            ->where('quantity', '>', 0)
            ->where('show', 1)
            ->with(['translations', 'images', 'price', 'brand.translations', 'category.parent'])
            ->get();

        Log::info('Products loaded: ' . $products->count());

        $locale = app()->getLocale();
        $fallbackLocale = 'ka';
        $successCount = 0;
        $errorCount = 0;

        foreach ($products as $product) {
            try {
                Log::info("Processing product ID: {$product->id}");

                // Get product image
                $productImage = $this->getProductImage($product);
                Log::debug("Product {$product->id} image: {$productImage}");

                // Get product price
                $productPrice = $this->getProductPrice($product);
                Log::debug("Product {$product->id} price: {$productPrice}");

                // Get additional images
                $productGallery = $this->getProductGallery($product);
                Log::debug("Product {$product->id} gallery count: " . count($productGallery));

                // Get translation with fallback
                $translation = $product->translations->where('locale', 'ka')->first() ?? '';

                if (!$translation) {
                    Log::warning("Product {$product->id} has no translation");
                    $errorCount++;
                    continue;
                }

                $brandTranslation = $product->brand->translations->where('locale', 'ka')->first();

                if (!$brandTranslation) {
                    Log::warning("Product {$product->id} brand has no translation");
                }
                $item = [
                    'link' => route('web.products.view', $translation->slug),
                    'id' => $product->id,
                    'title' => $translation->title,
                    'image_link' => $productImage,
                    'description' => strip_tags($translation->description),
                    'availability' => 'in stock',
                    'price' => $productPrice,
                    'brand' => $brandTranslation->title ?? 'Unknown',
                    'google_product_category' => $product->category->google_category_id ?? $product->category->parent->google_category_id,
                    'fb_product_category' => $product->category->facebook_category_id ?? $product->category->parent->facebook_category_id,
                    'condition' => 'new',
                    'additional_image_link' => $productGallery,
                    'product_type' => $product->category->parent->translations->where('locale', 'ka')->first()->title.' > '.$product->category->translations->where('locale', 'ka')->first()->title,
                ];
                if($productPrice > 150) {
                    $item['custom_label_0'] = 'თვეში ' . number_format($productPrice / 24) . '₾ დან';
                }

                if($product->price->discount_price > 0 OR !empty($product->price->discount_price)) {
                    $item['custom_label_1'] = $product->price->discount_price;
                }

                LaravelFacebookCatalog::addItem($item);
                $successCount++;
                Log::info("Product {$product->id} added successfully");

            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Error processing product {$product->id}: " . $e->getMessage(), [
                    'exception' => $e,
                    'product_id' => $product->id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        Log::info("Feed processing complete. Success: {$successCount}, Errors: {$errorCount}");

        try {
            $xml = LaravelFacebookCatalog::generate();
            Log::info('XML generated, length: ' . strlen($xml));

            // Debug first 200 characters
            Log::debug('XML first 200 chars: ' . substr($xml, 0, 200));

            // Check for BOM or whitespace
            $firstChars = substr($xml, 0, 10);
            $hexDump = bin2hex($firstChars);
            Log::debug('XML first 10 chars HEX: ' . $hexDump);

            if ($hexDump !== '3c3f786d6c207665') { // <?xml ve
                Log::warning('XML does not start with proper declaration. HEX: ' . $hexDump);
            }

            ob_end_clean();
            Log::info('Output buffer cleaned, returning response');

            return response($xml, 200, [
                'Content-Type' => 'application/xml; charset=utf-8',
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating XML: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return response('Error generating feed', 500);
        }
    }


    /**
     * Get product main image
     */
    private function getProductImage($product): string
    {
        try {
            if ($product->main_image && $product->main_image != 1) {
                return url('storage/' . $product->main_image);
            }

            if ($product->images->isNotEmpty() && !empty($product->images[0]->path)) {
                return url('storage/' . $product->images[0]->path);
            }

            Log::debug("Product {$product->id} using default image");
            return url('web-assets/img/no-product.png');

        } catch (\Exception $e) {
            Log::error("Error getting image for product {$product->id}: " . $e->getMessage());
            return url('web-assets/img/no-product.png');
        }
    }

    /**
     * Get product price (discount or regular)
     */
    private function getProductPrice($product): float
    {
        try {
            if ($product->price->discount_price) {
                return $product->price->discount_price;
            }

            return $product->price->regular_price ?? 0;

        } catch (\Exception $e) {
            Log::error("Error getting price for product {$product->id}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get product gallery images
     */
    private function getProductGallery($product): array
    {
        try {
            $gallery = [];

            if ($product->images->count() > 1) {
                foreach ($product->images as $image) {
                    $gallery[] = url('storage/' . $image->path);
                }
            }

            return $gallery;

        } catch (\Exception $e) {
            Log::error("Error getting gallery for product {$product->id}: " . $e->getMessage());
            return [];
        }
    }
}