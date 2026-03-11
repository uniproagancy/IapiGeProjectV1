<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\AltaID;
use App\Models\Product\Product;
use Illuminate\Support\Facades\Log;
use SoapClient;
use Exception;

class AltaController extends Controller
{
    //
    private $soapClient;
    private $wsdlUrl = 'http://extra.alta.com.ge/b2b/b2bEWS?WSDL'; // შენი WSDL URL

    public function __construct()
    {
        try {
            $this->soapClient = new SoapClient($this->wsdlUrl, [
                'trace' => 1,
                'exceptions' => true,
                'encoding' => 'UTF-8'
            ]);
        } catch (Exception $e) {
            throw new Exception('SOAP კლიენტის შეცდომა: ' . $e->getMessage());
        }
    }

    public function getProducts()
    {
        try {
            $params = [
                'user' => 'UNIPRO_CHI',
                'password' => 'CHI1457160',
                'item' => ''
            ];
            $response = $this->soapClient->GetPriceList($params);
            AltaID::truncate();
            foreach($response->PriceList->items->item as $item) {
                if($this->parseQtyText($item->qty_text)['quantity'] > 2) {
                    $show = 1;
                } else {
                    $show = 0;
                }
                Product::where('sku', 'ALTA-'.$item->item)->update([
                    'show' => $show,
                    'quantity' => $this->parseQtyText($item->qty_text)['quantity'],
                ]);
                Log::info('ALTA-'.$item->item.' Quantity Updated '.$this->parseQtyText($item->qty_text)['quantity'].' Show status:'.$show);
            }
        } catch (Exception $e) {
            throw new Exception('ფასების მიღება ვერ მოხერხდა: ' . $e->getMessage());
        }
    }

    private function parseQtyText(?string $qtyText): array
    {
        try {
            if (empty($qtyText)) {
                return [
                    'has_stock' => false,
                    'quantity' => 0,
                ];
            }
            $qtyText = strtolower(trim($qtyText));
            if (strpos($qtyText, '>=') === 0) {
                $minQty = (int)str_replace('>=', '', $qtyText);
                return [
                    'has_stock' => true,
                    'quantity' => max($minQty, 10), // Minimum 10
                ];
            }
            if (strpos($qtyText, '>') === 0) {
                $numStr = preg_replace('/[^0-9]/', '', $qtyText);
                $qty = (int)$numStr;
                return [
                    'has_stock' => $qty > 0,
                    'quantity' => $qty > 0 ? $qty : 0,
                ];
            }
            if (is_numeric($qtyText)) {
                $qty = (int)$qtyText;
                return [
                    'has_stock' => $qty > 0,
                    'quantity' => $qty,
                ];
            }
            if (preg_match('/out|not|unavailable|უ/i', $qtyText)) {
                return [
                    'has_stock' => false,
                    'quantity' => 0,
                ];
            }
            if (preg_match('/in stock|available|ხელმ|აქვ/i', $qtyText)) {
                return [
                    'has_stock' => true,
                    'quantity' => 5, // Default quantity
                ];
            }
            return [
                'has_stock' => true,
                'quantity' => 5,
            ];
        } catch (Exception $e) {
            Log::warning("⚠️  qty_text პარსის შეცდომა: {$qtyText} - {$e->getMessage()}");
            return [
                'has_stock' => false,
                'quantity' => 0,
            ];
        }
    }
}
