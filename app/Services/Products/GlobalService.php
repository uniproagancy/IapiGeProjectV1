<?php

namespace App\Services\Products;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GlobalService
{
    public function searchByName(string $name): ?array
    {
        try {
            // -------- TODO: რეალური Ushop API call --------
            // $resp = Http::timeout(30)->get('https://ushop.ge/api/search', ['q' => $name]);
            // if (!$resp->successful()) return null;
            // $first = $resp->json('products.0');
            // if (!$first) return null;
            // $detail = Http::get("https://ushop.ge/api/product/{$first['slug']}")->json('product');
            // return $this->normalize($detail);
            // ----------------------------------------------

            Log::info("🔎 Global/Ushop search (placeholder): {$name}");
            return null;

        } catch (\Throwable $e) {
            Log::error("GlobalService searchByName error [{$name}]: " . $e->getMessage());
            return null;
        }
    }

    private function normalize(array $data): array
    {
        return [
            'sku'         => $data['sku']         ?? null,
            'name'        => $data['name']        ?? null,
            'price'       => $data['price']       ?? null,
            'old_price'   => $data['old_price']   ?? null,
            'description' => $data['description'] ?? null,
            'images'      => $data['images']      ?? [],
            'category'    => $data['category']    ?? null,
            'brand'       => $data['brand']       ?? null,
        ];
    }
}