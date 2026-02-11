<?php

namespace App\Services\Products;

use App\Models\AltaID;
use SoapClient;
use Exception;

class AltaService
{
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

    public function getPriceList($itemCode = '')
    {
        try {
            $params = [
                'user' => 'UNIPRO_GP',
                'password' => 'unipro2020',
                'item' => $itemCode
            ];
            $response = $this->soapClient->GetPriceList($params);
            foreach($response->PriceList->items->item as $product_item)  {
                AltaID::create([
                    'product_id' => $product_item->item,
                ]);
            }
        } catch (Exception $e) {
            throw new Exception('ფასების მიღება ვერ მოხერხდა: ' . $e->getMessage());
        }
    }
}