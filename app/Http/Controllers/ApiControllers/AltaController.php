<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use SoapClient;
use Exception;

class AltaController extends Controller
{
    //
    private $soapClient;
    private $wsdlUrl = 'http://extra.alta.com.ge/soap?wsdl'; // შენი WSDL URL

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
            dd($response);
        } catch (Exception $e) {
            throw new Exception('ფასების მიღება ვერ მოხერხდა: ' . $e->getMessage());
        }
    }
}
