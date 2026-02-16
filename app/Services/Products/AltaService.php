<?php

namespace App\Services\Products;

use App\Jobs\AltaProductJob;
use App\Models\AltaID;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use SoapClient;

class AltaService
{
    private $soapClient;
    private $client;
    private $wsdlUrl = 'http://extra.alta.com.ge/b2b/b2bEWS?WSDL';

    public function __construct() {
        try {
            $this->client = new Client([
                'timeout' => 30,
                'connect_timeout' => 10,
            ]);
            $this->soapClient = new SoapClient($this->wsdlUrl, [
                'trace' => 1,
                'exceptions' => true,
                'encoding' => 'UTF-8'
            ]);
        } catch (Exception $e) {
            throw new Exception('SOAP კლიენტის შეცდომა: ' . $e->getMessage());
        }
    }

    public function getAltaB2B()
    {
        try {
            AltaID::truncate();
            $params = [
                'user' => 'UNIPRO_CHI',
                'password' => 'CHI1457160',
                'item' => '',
            ];
            $response = $this->soapClient->GetPriceList($params);
            if (!empty($response->PriceList) || !empty($response->PriceList->items->item)) {
                foreach($response->PriceList->items->item as $item) {
                    dd($item);
                    $productData = AltaID::create([
                        'product_id' => $item->item,
                        'quantity' => $this->parseQtyText($item->qty_text)['quantity'],
                    ]);
                    if($productData->quantity > 0) {
                        $target_url = 'https://alta.ge/search/'.$productData->product_id;
                        $url = "https://api.scrape.do/?url=" . urlencode($target_url) .
                            "&token=54ca3e2868ca407893b3316c254d6db6c146439c5b3";
                        $page_response = $this->client->get($url);
                        $html = (string)$page_response->getBody();

                            dd($html);
                        $url = "https://api.scrape.do/?url=" . urlencode($target_url) .
                            "&token=54ca3e2868ca407893b3316c254d6db6c146439c5b3";
                        $search = $this->client->request('GET', $url, [
                            'headers' => [
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                                'Accept' => 'application/json',
                            ]
                        ]);
                        dd($search);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception('ფასების მიღება ვერ მოხერხდა: ' . $e->getMessage());
        }
    }

    protected function parseQtyText(?string $qtyText): array
    {
        try {
            if (empty($qtyText)) {
                return [
                    'in_stock' => false,
                    'quantity' => 0,
                    'text' => $qtyText,
                ];
            }

            $qtyText = strtolower(trim($qtyText));

            // Case 1: ">=10"
            if (strpos($qtyText, '>=') === 0) {
                $minQty = (int)str_replace('>=', '', $qtyText);
                return [
                    'in_stock' => true,
                    'quantity' => max($minQty, 10),
                    'text' => $qtyText,
                ];
            }

            // Case 2: ">X"
            if (strpos($qtyText, '>') === 0) {
                $numStr = preg_replace('/[^0-9]/', '', $qtyText);
                $qty = (int)$numStr;
                return [
                    'in_stock' => $qty > 0,
                    'quantity' => $qty > 0 ? $qty : 0,
                    'text' => $qtyText,
                ];
            }

            // Case 3: Numeric
            if (is_numeric($qtyText)) {
                $qty = (int)$qtyText;
                return [
                    'in_stock' => $qty > 0,
                    'quantity' => $qty,
                    'text' => $qtyText,
                ];
            }

            // Case 4: "Out of Stock"
            if (preg_match('/out|not|unavailable|უ/i', $qtyText)) {
                return [
                    'in_stock' => false,
                    'quantity' => 0,
                    'text' => $qtyText,
                ];
            }

            // Case 5: "In Stock"
            if (preg_match('/in stock|available|ხელმ|აქვ/i', $qtyText)) {
                return [
                    'in_stock' => true,
                    'quantity' => 5,
                    'text' => $qtyText,
                ];
            }

            return [
                'in_stock' => false,
                'quantity' => 0,
                'text' => $qtyText,
            ];

        } catch (Exception $e) {
            Log::warning("⚠️  qty_text parsing error: {$qtyText}");
            return [
                'in_stock' => false,
                'quantity' => 0,
                'text' => $qtyText,
            ];
        }
    }

}