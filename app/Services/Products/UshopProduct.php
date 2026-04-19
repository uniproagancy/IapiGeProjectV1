<?php

namespace App\Services\Products;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class UshopProduct
{
    protected $client;
    protected $baseUrl = 'https://ushop.ge/iapi.php';

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    public function getProduct()
    {
        try {
            $response = $this->client->request('GET', $this->baseUrl, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'Accept' => 'application/json',
                ]
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            Log::error('CitrusProduct: ' . $e->getMessage());
            return [];
        }
    }
}