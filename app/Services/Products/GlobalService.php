<?php

namespace App\Services\Products;

use Illuminate\Support\Facades\Log;

class GlobalService
{
    protected CitrusProduct $citrus;

    public function __construct()
    {
        $this->citrus = new CitrusProduct();
    }

    /**
     * დასახელება → search → slug → product → ნორმალიზებული array | null
     */
    public function searchByName(string $name): ?array
    {
        $slug = $this->citrus->searchSlug($name);
        if (!$slug) {
            return null;
        }

        $resp    = $this->citrus->getProduct($slug);
        $product = $resp['product'] ?? $resp; // product key-ში ან root-ში

        if (empty($product) || empty($product['id'])) {
            Log::warning("🔎 Citrus: empty product for slug '{$slug}'");
            return null;
        }

        return $this->normalize($product);
    }

    private function normalize(array $p): array
    {
        // სურათები — large_images / images
        $images = [];
        if (!empty($p['large_images'])) {
            $images = $p['large_images'];
        } elseif (!empty($p['images'])) {
            $images = array_filter(array_map(fn($i) => $i['url'] ?? null, $p['images']));
        } elseif (!empty($p['image'])) {
            $images = [$p['image']];
        }

        // category — breadcrumb-ის ბოლო (ყველაზე კონკრეტული)
        $category = null;
        if (!empty($p['breadcrumb']) && is_array($p['breadcrumb'])) {
            $last     = end($p['breadcrumb']);
            $category = $last['name'] ?? null;
        }

        return [
            'sku'         => $p['sku']  ?? null,
            'name'        => $p['name'] ?? null,
            'price'       => $p['price'] ?? null,          // → regular_price
            'old_price'   => $p['old_price'] ?? null,
            'description' => $p['details']['description'] ?? ($p['seo']['meta_description'] ?? null),
            'images'      => array_values($images),
            'category'    => $category,
            'brand'       => $p['manufacturer']['name'] ?? null,
            'stock'       => $p['stock'] ?? 0,
        ];
    }
}