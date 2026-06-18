<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\EliteProduct;
use App\Services\Products\EliteScanService;
use Illuminate\Http\RedirectResponse;

class ElitController extends Controller
{
    public function scan(): RedirectResponse
    {
        $unsynced = EliteProduct::where('synced', false)->count();

        if ($unsynced === 0) {
            return back()->with('error', 'არ არის BarCode-ები სკანისთვის. ჯერ Excel ატვირთე.');
        }

        dispatch(function () {
            (new EliteScanService())->setRange(1, 35000)->scanAllIds();
        })->onQueue('elite');

        return back()->with('success', "Elite სკანი დაიწყო ($unsynced BarCode)");
    }
}