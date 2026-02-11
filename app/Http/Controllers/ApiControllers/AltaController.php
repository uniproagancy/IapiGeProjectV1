<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Services\Products\AltaService;
use Illuminate\Http\Request;

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
}
