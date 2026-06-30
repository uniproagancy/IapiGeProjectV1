<?php

namespace App\Http\Controllers;

use App\Models\Product\Product;
use Illuminate\Support\Facades\Log;
use MeeeetDev\LaravelFacebookCatalog\LaravelFacebookCatalog;

class FacebookFeedController extends Controller
{
    public function getFeed()
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        LaravelFacebookCatalog::setTitle('iapi.ge feed');
        LaravelFacebookCatalog::setDescription('iapi.ge product feed');
        LaravelFacebookCatalog::setLink('https://iapi.ge');
        LaravelFacebookCatalog::setCurrency('GEL');

        $successCount = 0;
        $errorCount   = 0;

        // chunk-ებად ვამუშავებთ — memory პრობლემა გვაქვს
        Product::where('active', 1)
            ->where('show', 1)
            ->whereHas('category')
            ->with([
                'translations',
                'images',
                'price',
                'brand.translations',
                'category.translations',
                'category.parent.translations', // ← fix: parent translations
            ])
            ->chunkById(200, function ($products) use (&$successCount, &$errorCount) {
                foreach ($products as $product) {
                    try {
                        // კატეგორია 21 — ფასი 30-ზე ნაკლები გამოვტოვოთ
                        if ($product->category_id == 21) {
                            $price    = $product->price->regular_price ?? 0;
                            $discount = $product->price->discount_price ?? 0;
                            if ($price < 30 || ($discount > 0 && $discount < 30)) {
                                continue;
                            }
                        }

                        $translation = $product->translations->where('locale', 'ka')->first();
                        if (!$translation) {
                            $errorCount++;
                            continue;
                        }

                        // description ცარიელია — გამოვტოვოთ (Facebook მოითხოვს)
                        $description = strip_tags($translation->description ?? '');
                        if (empty(trim($description))) {
                            $description = $translation->title; // fallback სახელზე
                        }

                        $brandTranslation = $product->brand?->translations->where('locale', 'ka')->first();
                        $brandName        = $brandTranslation?->title;

                        // Unknown ბრენდი — გამოვტოვოთ ან სახელი გამოვიყენოთ
                        if (empty($brandName) || $brandName === 'Unknown') {
                            $brandName = null; // Facebook-ს ცარიელი ჯობია Unknown-ზე
                        }

                        $categoryName = $product->category?->translations->where('locale', 'ka')->first()?->title;
                        $parentName   = $product->category?->parent?->translations->where('locale', 'ka')->first()?->title;

                        $googleCategoryId = $product->category?->google_category_id
                            ?? $product->category?->parent?->google_category_id
                            ?? null;

                        $regularPrice  = (float) ($product->price->regular_price ?? 0);
                        $discountPrice = (float) ($product->price->discount_price ?? 0);

                        // sale_price — მხოლოდ თუ 0-ზე მეტია
                        $salePrice = $discountPrice > 0 ? $discountPrice : null;

                        // რეალური ფასი (ყველაზე დაბალი)
                        $productPrice = $salePrice ?? $regularPrice;

                        $productImage   = $this->getProductImage($product);
                        $productGallery = $this->getProductGallery($product);

                        $item = [
                            'id'                           => $product->id,
                            'link'                         => route('web.products.view', $translation->slug),
                            'title'                        => $translation->title,
                            'description'                  => $description,
                            'image_link'                   => $productImage,
                            'availability'                 => 'in stock',
                            'condition'                    => 'new',
                            'price'                        => $regularPrice,
                            'brand'                        => $brandName,
                            'google_product_category'      => $googleCategoryId,
                            'quantity_to_sell_on_facebook' => intval($product->quantity * 10),
                            'additional_image_link'        => $productGallery,
                            'product_type'                 => $parentName && $categoryName
                                ? $parentName . ' > ' . $categoryName
                                : ($categoryName ?? ''),
                            'custom_label_2'               => $parentName ?? '',
                        ];

                        // sale_price მხოლოდ თუ არსებობს
                        if ($salePrice) {
                            $item['sale_price']    = $salePrice;
                            $item['custom_label_1'] = $salePrice;
                        }

                        // განვადება 150-ზე მეტზე
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

                gc_collect_cycles(); // memory გათავისუფლება
            });

        Log::info("FacebookFeed: success={$successCount} errors={$errorCount}");

        try {
            $xml = LaravelFacebookCatalog::generate();

            ob_end_clean();

            return response($xml, 200, [
                'Content-Type' => 'application/xml; charset=utf-8',
            ]);

        } catch (\Exception $e) {
            Log::error('FacebookFeed XML error: ' . $e->getMessage());
            ob_end_clean();
            return response('Error generating feed', 500);
        }
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
                ->take(10) // Facebook max 10 additional images
                ->map(fn($img) => url('storage/' . $img->path))
                ->toArray();

        } catch (\Exception $e) {
            return [];
        }
    }
}