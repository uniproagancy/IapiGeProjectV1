<?php

namespace App\Http\Controllers;

use App\Models\Product\Product;
use Illuminate\Support\Facades\Log;
use MeeeetDev\LaravelFacebookCatalog\LaravelFacebookCatalog;

class FacebookFeedController extends Controller
{
    private const CACHE_PATH = 'app/facebook-feed.xml';
    private const CACHE_TTL  = 3600; // 1 საათი

    public function getFeed()
    {
        $cachePath = storage_path(self::CACHE_PATH);

        // თუ cache არსებობს და 1 საათზე ახალია — პირდაპირ დავაბრუნოთ
        if (file_exists($cachePath) && (time() - filemtime($cachePath)) < self::CACHE_TTL) {
            Log::info('FacebookFeed: cache-იდან დაბრუნდა');
            return $this->xmlResponse(file_get_contents($cachePath));
        }

        // ახლიდან generate
        Log::info('FacebookFeed: ახლიდან generate იწყება');
        $xml = $this->generateFeed();

        // ფაილში შენახვა
        file_put_contents($cachePath, $xml);
        Log::info('FacebookFeed: cache ფაილი განახლდა');

        return $this->xmlResponse($xml);
    }

    public function regenerate(): void
    {
        Log::info('FacebookFeed: scheduled regenerate დაიწყო');
        $xml = $this->generateFeed();
        file_put_contents(storage_path(self::CACHE_PATH), $xml);
        Log::info('FacebookFeed: scheduled regenerate დასრულდა');
    }

    private function generateFeed(): string
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        // ყველა output buffer გავასუფთაოთ
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        LaravelFacebookCatalog::setTitle('iapi.ge feed');
        // ...

        // generate-ის დროს ob_start არ გამოვიყენოთ
        $xml = LaravelFacebookCatalog::generate();

        // ბოლოს კიდევ ერთხელ გავასუფთაოთ
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return $xml;
    }

    private function xmlResponse(string $xml)
    {
        return response($xml, 200, [
            'Content-Type'  => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
            'Last-Modified' => gmdate('D, d M Y H:i:s', filemtime(storage_path(self::CACHE_PATH)) ?: time()) . ' GMT',
        ]);
    }

    private function getProductImage($product): string
    {
        try {
            if ($product->main_image && $product->main_image != 1) {
                return url('storage/' . $product->main_image);
            }

            if ($product->images->isNotEmpty() && !empty($product->images[0]->path)) {
                return url('storage/' . $product->images[0]->path);
            }

            return url('web-assets/img/no-product.png');

        } catch (\Exception $e) {
            return url('web-assets/img/no-product.png');
        }
    }

    private function getProductGallery($product): array
    {
        try {
            if ($product->images->count() <= 1) return [];

            return $product->images
                ->take(10)
                ->map(fn($img) => url('storage/' . $img->path))
                ->toArray();

        } catch (\Exception $e) {
            return [];
        }
    }
}