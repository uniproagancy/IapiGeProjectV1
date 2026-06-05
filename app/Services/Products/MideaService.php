<?php

namespace App\Services\Products;

use Illuminate\Support\Facades\Log;

class MideaService
{
    protected MideaProduct $midea;

    private const IMAGE_BASE = 'https://www.midea.ge/uploads/products/';

    public function __construct()
    {
        $this->midea = new MideaProduct();
    }

    /**
     * მოდელით ძებნა → ნორმალიზებული array | null
     */
    public function searchByName(string $model): ?array
    {
        $products = $this->midea->search($model);
        if (empty($products)) {
            return null;
        }

        // ზუსტი model_number match, თუ არა — პირველი
        $match = null;
        foreach ($products as $p) {
            if (isset($p['model_number'])
                && mb_strtolower(trim($p['model_number'])) === mb_strtolower(trim($model))) {
                $match = $p;
                break;
            }
        }
        if (!$match) {
            $match = $products[0];
        }

        return $this->normalize($match);
    }

    private function normalize(array $p): array
    {
        // სურათები — image + gallery
        $images = [];
        if (!empty($p['image'])) {
            $images[] = self::IMAGE_BASE . $p['image'];
        }
        if (!empty($p['gallery'])) {
            $gallery = json_decode($p['gallery'], true);
            if (is_array($gallery)) {
                foreach ($gallery as $g) {
                    if (!empty($g)) {
                        $images[] = self::IMAGE_BASE . $g;
                    }
                }
            }
        }

        // ფასი — sale_price თუ > 0, თორემ price
        $price    = (float) ($p['price'] ?? 0);
        $sale     = (float) ($p['sale_price'] ?? 0);
        $oldPrice = $sale > 0 ? $price : null;
        $current  = $sale > 0 ? $sale : $price;

        return [
            'sku'         => $p['model_number'] ?? ($p['id'] ?? null),
            'name'        => $p['title'] ?? null,
            'price'       => $current,
            'old_price'   => $oldPrice,
            'description' => $p['text'] ?? ($p['short_text'] ?? null),
            'images'      => array_values(array_unique($images)),
            'brand'       => 'Midea',
            'stock'       => (int) ($p['in_stock'] ?? 0),
        ];
    }
}