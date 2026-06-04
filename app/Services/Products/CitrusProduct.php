<?php

namespace App\Services\Products;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class CitrusProduct
{
    protected $client;
    protected $searchUrl  = 'https://citrus.ge/api/search';
    protected $productUrl = 'https://citrus.ge/api/product';

    public function __construct()
    {
        $this->client = new Client([
            'timeout'         => 30,
            'connect_timeout' => 10,
        ]);
    }

    public function searchSlug(string $name): ?string
    {
        $attempts = 0;

        while ($attempts < 2) {
            try {
                $response = $this->client->request('GET', $this->searchUrl, [
                    'query'   => ['q' => $name],
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        'Accept'     => 'application/json',
                    ],
                ]);

                $data     = json_decode($response->getBody()->getContents(), true);
                $products = $data['products'] ?? [];

                if (empty($products)) {
                    Log::warning("🔎 Citrus: no result for '{$name}'");
                    return null;
                }

                return $products[0]['slug'] ?? null;

            } catch (Exception $e) {
                $attempts++;
                Log::warning("⏳ Citrus searchSlug retry {$attempts} [{$name}]: " . $e->getMessage());
                usleep(500000);
            }
        }

        Log::error("CitrusProduct searchSlug failed [{$name}]");
        return null;
    }

    public function getProduct(string $slug): array
    {
        $attempts = 0;

        while ($attempts < 2) {
            try {
                $response = $this->client->request('GET', $this->productUrl . '/' . $slug, [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        'Accept'     => 'application/json',
                    ],
                ]);

                return json_decode($response->getBody()->getContents(), true) ?: [];

            } catch (Exception $e) {
                $attempts++;
                Log::warning("⏳ Citrus getProduct retry {$attempts} [{$slug}]: " . $e->getMessage());
                usleep(500000);
            }
        }

        Log::error("CitrusProduct getProduct failed [{$slug}]");
        return [];
    }
}