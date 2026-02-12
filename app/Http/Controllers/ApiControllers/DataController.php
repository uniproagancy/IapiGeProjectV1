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

class DataController extends Controller
{
    public function loadJson()
    {
        // JSON ფაილის path
        $jsonPath = storage_path('app/data.json');

        // ფაილის არსებობის შემოწმება
        if (!file_exists($jsonPath)) {
            return response()->json(['error' => 'ფაილი ვერ მოიძებნა'], 404);
        }

        // ფაილის წაკითხვა
        $jsonContent = file_get_contents($jsonPath);

        // JSON დეკოდირება
        $data = json_decode($jsonContent, true);

        if ($data === null) {
            return response()->json(['error' => 'JSON შეცდომა: ' . json_last_error_msg()], 400);
        }

        return response()->json($data, 200);
    }

    // მონაცემების ბაზაში შენახვა
    public function importData()
    {
        $jsonPath = storage_path('app/Json.json');
        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);
        foreach($data['Sheet1'] as $productData) {
        $categoryId = 140; // default value
        if ($productData['data3'] === 'გაყიდვაშია') {
            $availability = 1;
            $show = 1;
            $active = 1;
        } else {
            $availability = 0;
            $show = 0;
            $active = 0;
        }
        $product = Product::create([
            'supplier_product_id' => !empty($productData['product_code']) ? intval($productData['product_code']) : 0,
            'brand_id' => 143,
            'category_id' => $categoryId,
            'sku' => 'COMFO-' . (!empty($productData['product_code']) ? intval($productData['product_code']) : ''),
            'supplier_id' => 4,
            'main_image' => $productData['image'] ?? '',
            'quantity' => 5,
            'in_stock' => $availability,
            'show' => $show,
            'active' => $active,
        ]);

        // Price ის მუშაობა
        $price = 0;
        $oldPrice = null;
        $discountPercent = 0;
        $productPrice = $productData['price2'] ?? $productData['price'] ?? 0;
        $discountPrice = $productData['price2'] ? $productData['price'] : null;
        $discount = (($productPrice - $discountPrice) / $productPrice) * 100;

        if (!empty($discount)) {
            preg_match('/-?\d+/', $discount, $matches);
            if (!empty($matches[0])) {
                $discountPercent = abs(intval($matches[0])) ;
            }
        }

        ProductPrice::create([
            'product_id' => $product->id,
            'dealer_price' => ($productPrice + 20) / 100,
            'regular_price' => ($productPrice + 20) / 100,
            'discount_price' => ($discountPrice + 20) / 100,
            'discount_percent' => abs(intval($discount)) - 4,
        ]);

        // ProductTranslation შექმნა
        ProductTranslation::create([
            'product_id' => $product->id,
            'locale' => 'ka',
            'title' => $productData['data'] ?? 'ტიტული არ მოიძებნა',
            'slug' => Str::slug($productData['data'] ?? 'product') . "-{$product->id}",
            'description' => !empty($productData['product_description']) ? $productData['product_description'] : ' ',
            'keywords' => '',
        ]);

        if (!empty($productData['image_9'])) {
            $this->downloadAndSaveImages($product, $productData['image_9']);
        }
        }
        return response()->json([
            'message' => 'მონაცემები წარმატებით იმპორტირდა',
            'product_id' => $product->id,
            'title' => $productData['data'] ?? '',
        ]);
    }

    /**
     * category_name ის მიხედვით category_id ის ძებნა
     */
    protected function getCategoryIdByName(string $categoryName): ?int
    {
        try {
            $category = ProductCategoryTranslation::where('name', $categoryName)
                ->where('locale', 'ka')
                ->first();

            if ($category) {
                return $category->product_category_id;
            }

            Log::warning("⚠️  Category not found: {$categoryName}");
            return null;

        } catch (\Exception $e) {
            Log::warning("⚠️  Error getting category: {$e->getMessage()}");
            return null;
        }
    }

    protected function getImageExtension(string $url): string
    {
        try {
            $parsed = parse_url($url);
            $path = $parsed['path'] ?? '';
            $ext = pathinfo($path, PATHINFO_EXTENSION);

            $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            return in_array(strtolower($ext), $validExtensions) ? strtolower($ext) : 'jpg';

        } catch (\Exception $e) {
            Log::warning("⚠️  Error getting image extension: {$e->getMessage()}");
            return 'jpg';
        }
    }

    /**
     * სურათების ჩამოწერა და შენახვა
     * image_1 სვეტი - ერთი ხაზით რამდენიმე URL (გამოყოფილი \n, ,, ; სიმბოლოებით)
     */
    protected function downloadAndSaveImages($product, $imageString): array
    {
        $images = [];
        $isMainImageSet = false;

        // image_1 სტრინგი მასივად გადაქცევა (სხვადსხვა გამოყოფილი)
        $imageUrls = $this->parseImageString($imageString);

        if (empty($imageUrls)) {
            Log::warning("⚠️  No images found for product: {$product->id}");
            return [];
        }

        foreach ($imageUrls as $index => $imageUrl) {
            $imageUrl = trim($imageUrl);
            if (empty($imageUrl)) {
                continue;
            }
            try {
                $response = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
                    ->get($imageUrl);

                if (!$response->successful()) {
                    Log::warning("⚠️  Failed to download image: {$imageUrl} (Status: {$response->status()})");
                    continue;
                }

                // ფაილის გაფართოების განსაზღვრა
                $ext = $this->getImageExtension($imageUrl);
                $filename = Str::random(40) . '.' . $ext;
                $path = "uploads/products/{$product->id}/{$filename}";

                // სურათის შენახვა
                Storage::disk('public')->put($path, $response->body());

                // პირველი სურათი - main image
                if (!$isMainImageSet) {
                    $product->update(['main_image' => $path]);
                    $isMainImageSet = true;
                    Log::info("✓ Main image set for product {$product->id}: {$path}");
                } else {
                    // დანარჩენი სურათები - gallery
                    $images[] = [
                        'product_id' => $product->id,
                        'path' => $path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    Log::info("✓ Image saved for product {$product->id}: {$path}");
                }

            } catch (\Exception $e) {
                Log::warning("⚠️  Error downloading image {$imageUrl}: {$e->getMessage()}");
                continue;
            }
        }

        // სურათების მასიური შენახვა
        if (!empty($images)) {
            ProductImage::insert($images);
            Log::info("✓ Inserted " . count($images) . " images for product {$product->id}");
        }

        return $images;
    }

    /**
     * image_1 სტრინგი მასივად გადაქცევა
     * მხარს უჭერს სხვადსხვა გამოყოფილებას: \n, ,, ;, |
     */
    protected function parseImageString(string $imageString): array
    {
        if (empty($imageString)) {
            return [];
        }

        // მრავალი გამოყოფილების მხარდაჭერა
        $imageString = str_replace(['\n', '\r\n', '\r'], "\n", $imageString);

        // გაყოფა სხვადსხვა გამოყოფილებით
        $separator = null;
        if (strpos($imageString, "\n") !== false) {
            $separator = "\n";
        } elseif (strpos($imageString, ',') !== false) {
            $separator = ',';
        } elseif (strpos($imageString, ';') !== false) {
            $separator = ';';
        } elseif (strpos($imageString, '|') !== false) {
            $separator = '|';
        } else {
            // მხოლოდ ერთი URL
            return [trim($imageString)];
        }

        // გაყოფა და დაფილტვა
        $urls = array_filter(
            array_map('trim', explode($separator, $imageString)),
            function($url) {
                return !empty($url) && (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0);
            }
        );

        return array_values($urls); // რე-ინდექსირება
    }
}