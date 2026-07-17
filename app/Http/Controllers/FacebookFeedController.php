<?php

namespace App\Http\Controllers;

use App\Models\Product\Product;
use Illuminate\Support\Facades\Log;
use MeeeetDev\LaravelFacebookCatalog\LaravelFacebookCatalog;

class FacebookFeedController extends Controller
{
    private const CACHE_PATH  = 'app/facebook-feed.xml';
    private const CACHE_TTL   = 86400; // 24 საათი
    private const MIN_PRICE   = 70;    // ← მინიმალური ფასი — შეცვალე საჭიროებისამებრ
    private const EXCLUDED_CATEGORY_IDS = [21]; // ← ამოსაშლელი კატეგორიები

    public function getFeed()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $cachePath = storage_path(self::CACHE_PATH);

        if (file_exists($cachePath) && (time() - filemtime($cachePath)) < self::CACHE_TTL) {
            Log::info('FacebookFeed: cache-იდან დაბრუნდა');
            return $this->xmlResponse(file_get_contents($cachePath), $cachePath);
        }

        Log::info('FacebookFeed: ახლიდან generate იწყება');
        $xml = $this->generateFeed();

        file_put_contents($cachePath, $xml);
        Log::info('FacebookFeed: cache ფაილი განახლდა');

        return $this->xmlResponse($xml, $cachePath);
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

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        LaravelFacebookCatalog::$container = null;

        LaravelFacebookCatalog::setTitle('iapi.ge feed');
        LaravelFacebookCatalog::setDescription('iapi.ge product feed');
        LaravelFacebookCatalog::setLink('https://iapi.ge');
        LaravelFacebookCatalog::setCurrency('GEL');

        $successCount = 0;
        $errorCount   = 0;
        $skippedPrice = 0;

        Product::where('active', 1)
            ->where('show', 1)
            ->whereHas('category')
            ->whereNotIn('category_id', self::EXCLUDED_CATEGORY_IDS) // ← კატეგორია 21 გამოირიცხა
            ->with([
                'translations',
                'images',
                'price',
                'brand.translations',
                'category.translations',
                'category.parent.translations',
            ])
            ->chunkById(200, function ($products) use (&$successCount, &$errorCount, &$skippedPrice) {
                foreach ($products as $product) {
                    try {
                        // ფასი null-ია — გამოვტოვოთ
                        if (!$product->price) {
                            $errorCount++;
                            continue;
                        }

                        // ფასი MIN_PRICE-ზე ნაკლებია — გამოვტოვოთ
                        $regularPrice  = (float) ($product->price->regular_price ?? 0);
                        $discountPrice = (float) ($product->price->discount_price ?? 0);
                        $finalPrice    = $discountPrice > 0 ? $discountPrice : $regularPrice;

                        if ($finalPrice < self::MIN_PRICE) {
                            $skippedPrice++;
                            continue;
                        }

                        $translation = $product->translations->where('locale', 'ka')->first();
                        if (!$translation) {
                            $errorCount++;
                            continue;
                        }

                        // description
                        $description = trim(strip_tags($translation->description ?? ''));
                        if (empty($description)) {
                            $description = $translation->title;
                        }

                        // ბრენდი
                        $brandName = $product->brand?->translations
                            ->where('locale', 'ka')->first()?->title;
                        if (empty($brandName) || $brandName === 'Unknown') {
                            $brandName = 'უცნობი';
                        }

                        // კატეგორია
                        $categoryName = $product->category?->translations
                            ->where('locale', 'ka')->first()?->title;
                        $parentName   = $product->category?->parent?->translations
                            ->where('locale', 'ka')->first()?->title;

                        // Google კატეგორია
                        $googleCategoryId = $product->category?->google_category_id
                            ?? $product->category?->parent?->google_category_id
                            ?? null;

                        $salePrice    = $discountPrice > 0 ? $discountPrice : null;
                        $productPrice = $salePrice ?? $regularPrice;

                        // SKU prefix — custom_label_3
                        $skuParts  = explode('-', $product->sku ?? '', 2);
                        $skuPrefix = $skuParts[0] ?? '';

                        $item = [
                            'id'                           => $product->id,
                            'link'                         => route('web.products.view', $translation->slug),
                            'title'                        => $translation->title,
                            'description'                  => $description,
                            'image_link'                   => $this->getProductImage($product),
                            'availability'                 => 'in stock',
                            'condition'                    => 'new',
                            'price'                        => $regularPrice,
                            'brand'                        => $brandName,
                            'google_product_category'      => $googleCategoryId,
                            'quantity_to_sell_on_facebook' => intval($product->quantity * 10),
                            'additional_image_link'        => $this->getProductGallery($product),
                            'product_type'                 => $parentName && $categoryName
                                ? $parentName . ' > ' . $categoryName
                                : ($categoryName ?? ''),
                            'custom_label_2'               => $parentName ?? '',
                            'custom_label_3'               => $skuPrefix,
                        ];

                        // sale_price — მხოლოდ თუ არსებობს
                        if ($salePrice) {
                            $item['sale_price']     = $salePrice;
                            $item['custom_label_1'] = $salePrice;
                        }

                        // განვადება — 150-ზე მეტზე
                        if ($productPrice > 150) {
                            $item['custom_label_0'] = 'თვეში ' . number_format($productPrice / 24) . '₾ დან';
                        }

                        LaravelFacebookCatalog::addItem($item);
                        $successCount++;

                    } catch (\Exception $e) {
                        $errorCount++;
                        Log::error("FacebookFeed error product {$product->id}: " . $e->getMessage());
                    }
                }

                gc_collect_cycles();
            });

        Log::info("FacebookFeed: success={$successCount} errors={$errorCount} skipped_price={$skippedPrice} min_price=" . self::MIN_PRICE);

        $xml = LaravelFacebookCatalog::generate();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $xml = preg_replace('/^[\s\xEF\xBB\xBF]+/', '', $xml);
        $xml = preg_replace('/(<\?xml[^>]+\?>)\s*(<\?xml[^>]+\?>)+/s', '$1', $xml);
        $xml = preg_replace('/(<\?xml[^>]+\?>)\s+/', "$1\n", $xml);
        $xml = ltrim($xml);

        return $xml;
    }

    private function xmlResponse(string $xml, string $cachePath): \Illuminate\Http\Response
    {
        return response($xml, 200, [
            'Content-Type'  => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
            'Last-Modified' => gmdate('D, d M Y H:i:s', file_exists($cachePath) ? filemtime($cachePath) : time()) . ' GMT',
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