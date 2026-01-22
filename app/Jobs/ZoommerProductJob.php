<?php

namespace App\Jobs;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductFullSpecificationItem;
use App\Models\Product\ProductFullSpecificationSection;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductShortSpecification;
use App\Models\Product\ProductTranslation;
use App\Models\Product\ProductVariation;
use App\Models\Product\ProductVariationItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Translation\GoogleTranslation;

class ZoommerProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $productData;
    protected array $productAvailability;
    public int $tries = 10;
    public int $timeout = 120;

    public function __construct(array $productData, array $productAvailability = [])
    {
        $this->productData = $productData;
        $this->productAvailability = $productAvailability;
    }

    public function handle(): void
    {
        try {
            $this->saveProductWithVariants($this->productData, $this->productAvailability);
        } catch (\Exception $e) {
            $this->release(60);
        }
    }

    public function saveProductWithVariants(array $productData, array $productAvailability): void
    {
        $hasStock = $this->checkTbilisiStock($productAvailability);
        if (Product::where('supplier_product_id', $productData['id'])->exists()) {
            $this->updateExistingProduct($productData, $hasStock);
        } else {
            if($hasStock) {
                $this->createNewProduct($productData, $hasStock);
            }
        }
    }

    private function checkTbilisiStock(array $availability): bool
    {
        return collect($availability)
            ->where('city', 'Tbilisi')
            ->contains(fn($store) => $store['inStock'] === true);
    }

    private function updateExistingProduct(array $productData, bool $hasStock): void
    {
        $product = Product::where('supplier_product_id', $productData['id'])->first();
        $productPrice = $productData['previousPrice'] ?? $productData['price'];
        $discountPrice = $productData['previousPrice'] ? $productData['price'] : null;
        $product->price()->update([
            'dealer_price' => $productPrice,
            'regular_price' => $productPrice,
            'discount_price' => $discountPrice,
            'discount_percent' => $productData['discountPercent'] ?? 0,
        ]);
        $product->update([
            'quantity' => $hasStock ? 5 : 0,
            'in_stock' => $hasStock ? 1 : 0,
            'show' => $hasStock ? 1 : 0,
        ]);
    }

    private function createNewProduct(array $productData, bool $hasStock): void
    {
        DB::transaction(function () use ($productData, $hasStock) {
            $brand = collect($productData['specificationGroup'])
                ->firstWhere('groupName', 'Brand')
            ['specifications'][0]['specificationMeaning'];
            if(!empty($brand)) {
                $get_brand = ProductBrand::whereHas('translations', function ($subQuery) use($brand) {
                    $subQuery->where('title', 'like', $brand);
                })->first();
                $brand_id = $get_brand->id;
            } else {
                $brand_id = 6;
            }
            $product = Product::create([
                'supplier_product_id' => $productData['id'],
                'brand_id' => $brand_id,
                'category_id' => 4,
                'sku' => $productData['barCode'],
                'supplier_id' => 4,
                'main_image' => 1,
                'active' => 1,
                'quantity' => $hasStock ? 5 : 0,
                'in_stock' => $hasStock ? 1 : 0,
                'show' => $hasStock ? 1 : 0,
            ]);
            $this->createPrice($product, $productData);
            $this->createTranslations($product, $productData);
            $this->createFullSpecifications($product, $productData);
            $this->createVariations($product, $productData);
            $this->downloadImages($product, $productData);
            $this->createShortSpecifications($product, $productData);
        });
    }

    private function createPrice(Product $product, array $productData): void
    {
        $productPrice = $productData['previousPrice'] ?? $productData['price'];
        $discountPrice = $productData['previousPrice'] ? $productData['price'] : null;
        $markup = $productPrice > 1000 ? 0.05 : 0.10;
        $finalPrice = $productPrice * (1 + $markup);
        ProductPrice::create([
            'product_id' => $product->id,
            'dealer_price' => $finalPrice,
            'regular_price' => $finalPrice,
            'discount_price' => $discountPrice,
            'discount_percent' => $productData['discountPercent'] ?? 0,
        ]);
    }

    private function createTranslations(Product $product, array $productData): void
    {
        $locales = ['ka', 'en', 'ru'];
        foreach ($locales as $locale) {
            $baseSlug = Str::slug($productData['name'], '-');
            $slugWithId = "{$baseSlug}-{$product->id}";
            ProductTranslation::create([
                'product_id' => $product->id,
                'locale' => $locale,
                'title' => $productData['name'],
                'slug' => $slugWithId,
                'description' => $locale === 'ka' ? $productData['description'] : null,
                'keywords' => null,
            ]);
        }
    }

    private function createVariations(Product $product, array $productData): void
    {
        if (empty($productData['keySpecification'])) {
            return;
        }
        foreach ($productData['keySpecification'] as $specification) {
            $variation = ProductVariation::create([
                'product_id' => $product->id,
                'name' => $specification['specificationName'],
                'value' => $specification['specificationMeaning'],
            ]);
            if (!empty($specification['specificationMeaningsList'])) {
                foreach ($specification['specificationMeaningsList'] as $item) {
                    ProductVariationItem::create([
                        'variation_id' => $variation->id,
                        'is_color' => $item['isColor'] ? 1 : 0,
                        'supplier_product_id' => $item['productId'] ?? null,
                        'value' => $item['value'],
                    ]);
                }
            }
        }
    }

    private function createFullSpecifications(Product $product, array $productData): void
    {
        if (empty($productData['specificationGroup'])) {
            return;
        }
        foreach ($productData['specificationGroup'] as $specificationGroup) {
            $section = ProductFullSpecificationSection::create([
                'product_id' => $product->id,
                'name' => (new GoogleTranslation)->translateToGeorgian($specificationGroup['groupName']),
            ]);
            if (!empty($specificationGroup['specifications'])) {
                foreach ($specificationGroup['specifications'] as $spec) {
                    if(!empty($spec['specificationLinkedUrl'])) {
                        $filter = true;
                    } else {
                        $filter = false;
                    }
                    $specification_name = (new GoogleTranslation)->translateToGeorgian($spec['specificationName']);
                    $specification_value = $spec['specificationMeaning'];
                    ProductFullSpecificationItem::create([
                        'section_id' => $section->id,
                        'name' => $specification_name,
                        'value' => $specification_value,
                        'filter' => $filter,
                    ]);
                }
            }
        }
    }

    private function downloadImages(Product $product, array $productData): void
    {
        if (empty($productData['images'])) {
            return;
        }
        foreach ($productData['images'] as $index => $imageUrl) {
            try {
                $get_image = Http::timeout(30)->get($imageUrl);
                if (!$get_image->successful()) {
                    continue;
                }
                $ext = $this->getImageExtension($imageUrl);
                $filename = Str::random(40) . '.' . $ext;
                $path = "uploads/products/{$product->id}/{$filename}";
                Storage::disk('public')->put($path, $get_image->body());
                if ($index === 0) {
                    $product->update(['main_image' => $path]);
                } else {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $path,
                    ]);
                }
            } catch (\Exception $e) {
                return;
            }
        }
    }

    private function createShortSpecifications(Product $product, array $productData): void
    {
        if (empty($productData['mainSpecification'])) {
            return;
        }
        foreach ($productData['mainSpecification'] as $spec) {
            ProductShortSpecification::create([
                'product_id' => $product->id,
                'name' => (new GoogleTranslation)->translateToGeorgian($spec['specificationName']),
                'value' => (new GoogleTranslation)->translateToGeorgian($spec['specificationMeaning']),
            ]);
        }
    }

    protected function getImageExtension(string $url): string
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return !empty($ext) ? $ext : 'jpg';
    }
}