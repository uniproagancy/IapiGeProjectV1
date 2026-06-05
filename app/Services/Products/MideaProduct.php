<?php

namespace App\Services\Products;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class MideaProduct
{
    protected $client;
    protected string $searchUrl = 'https://www.midea.ge/ka/search';

    public function __construct()
    {
        $this->client = new Client([
            'timeout'         => 30,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * keyword-ით ძებნა → products array
     */
    public function search(string $keyword): array
    {
        $attempts = 0;

        while ($attempts < 2) {
            try {
                $response = $this->client->request('POST', $this->searchUrl, [
                    'headers' => [
                        'User-Agent'       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        'Accept'           => 'application/json, text/plain, */*',
                        'X-Requested-With' => 'XMLHttpRequest',
                    ],
                    'form_params' => [
                        'keyword' => $keyword,
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                if (($data['status'] ?? 0) != 1 || empty($data['products'])) {
                    Log::warning("🔎 Midea: no result for '{$keyword}'");
                    return [];
                }

                return $data['products'];

            } catch (Exception $e) {
                $attempts++;
                Log::warning("⏳ Midea search retry {$attempts} [{$keyword}]: " . $e->getMessage());
                usleep(500000);
            }
        }

        Log::error("Midea search failed [{$keyword}]");
        return [];
    }
}