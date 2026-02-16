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
    private $wsdlUrl = 'http://extra.alta.com.ge/b2b/b2bEWS?WSDL';

    public function __construct() {
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

    public function getAltaB2B()
    {
        try {
            $params = [
                'user' => 'UNIPRO_CHI',
                'password' => 'CHI1457160',
                'item' => '',
            ];
            $response = $this->soapClient->GetPriceList($params);
            dd($response);
            foreach($response->PriceList->items->item as $product_item)  {

            }
        } catch (Exception $e) {
            throw new Exception('ფასების მიღება ვერ მოხერხდა: ' . $e->getMessage());
        }
    }

}