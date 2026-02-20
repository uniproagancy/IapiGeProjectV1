<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\AltaID;
use App\Models\Product\Product;
use App\Services\Products\AltaProduct;
use App\Services\Products\AltaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SoapClient;

class AltaController extends Controller
{
    //
    private $priceService;

    public function __construct(AltaService $priceService)
    {
        $this->priceService = $priceService;
    }

    public function scan()
    {
        return app(AltaService::class)->setIdRange(1, 60000)->scanAllIds();
    }

    public function AltaIDS()
    {
        $client = new SoapClient('http://extra.alta.com.ge/b2b/b2bEWS?WSDL', [
            'trace'              => 1,
            'exceptions'         => true,
            'encoding'           => 'UTF-8',
            'connection_timeout' => 30,
        ]);

        $result = $client->GetPriceList([
            'user'     => 'UNIPRO_ICH',
            'password' => 'CHI1457160',
            'item' => '',
        ]);
        dd($result->PriceList->items);
    }

    public function updateActive()
    {
        $ids = AltaID::all();
        foreach($ids as $id) {
            Product::where(['sku' => 'ALTA-'.$id['product_id']])->update(['active' => 1, 'show' => 1]);
            Log::warning('ALTA-'.$id['product_id'].' Updated');
        }
    }
}
