<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\AltaID;
use App\Models\Product\Product;
use App\Services\Products\AltaProduct;
use App\Services\Products\AltaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        return app(AltaProduct::class)->scanAllIds();
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
