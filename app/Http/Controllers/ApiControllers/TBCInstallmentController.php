<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Order\OrderTransaction;
use Illuminate\Http\Request;

class TBCInstallmentController extends Controller
{
    //
    public function __construct() {

    }

    public function status() {

        $transactions = OrderTransaction::where('order_id', $order->id)->get();


        $sessionId = 'შენი_სეშენ_იდი'; // ✅ შეცვალე

        $response = Http::withHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->get("https://api.tbcbank.ge/v1/online-installments/applications/{$sessionId}/status", [
            'merchantKey' => '000000000-ce21da5e-da92-48f3-8009-4d438cbcc137',
        ]);
        dd($response->json());
    }
}
