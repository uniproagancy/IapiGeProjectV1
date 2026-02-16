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
    private $altaService;

    public function __construct(AltaService $altaService)
    {
        $this->altaService = $altaService;
    }

    public function scan()
    {
        return $this->altaService->getAltaB2B();
    }
}
