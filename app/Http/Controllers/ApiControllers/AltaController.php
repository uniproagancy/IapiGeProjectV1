<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\AltaID;
use App\Models\Product\Product;
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

    public function altaTrash()
    {
        try {
            $username = 'UNIPRO_GP';
            $password = 'unipro2020';

            $priceList = $this->priceService->getPriceList(
                $username,
                $password,
            );

            return response()->json([
                'status' => 'success',
                'data' => $priceList
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
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
