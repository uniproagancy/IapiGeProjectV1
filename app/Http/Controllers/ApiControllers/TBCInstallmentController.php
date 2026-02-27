<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\OrderTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TBCInstallmentController extends Controller
{
    //
    public function __construct() {

    }

    public function token()
    {

        $response = Http::withHeaders([
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ])->asForm()->post('https://api.tbcbank.ge/oauth/token', [
            'client_id'     => 'rpaBGYDgUP6qC07OxkJjxN3jf6SLcwsZ',
            'client_secret' => 'tyttcQg6eDCiW1Y1',
            'grant_type' => 'client_credentials',
            'scope'      => 'online_installments',
        ]);
        return $response->json();
    }


    public function status() {
        dd($this->token());

        $orders = Order::whereIn('payment_id', [7])
            ->where('payment_status_id', 1)
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        foreach ($orders as $order) {
            if (empty($order->transaction->payment_order_id)) {
                continue;
            }
            try {

                $sessionId = $order->transaction->payment_order_id; // ✅ შეცვალე

                $response = Http::withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->token,
                ])->get("https://api.tbcbank.ge/v1/online-installments/applications/{$sessionId}/status", [
                    'merchantKey' => '416353635-82138e94-8cd4-4553-98ef-195f7dfdbe3d',
                ]);


//                dd(env('TBC_INSTALLMENT_MERCHANT_KEY'), $response->json());

//                $newStatus = $orderData['installment_status'] === 'success' ? 2 : 3;
//
//                Order::find($orderData['shop_order_id'])->update([
//                    'payment_status_id' => $newStatus,
//                ]);
//
//                // ✅ Purchase Pixel — მხოლოდ success-ზე
//                if ($newStatus === 2) {
//                    $this->trackPurchase(Order::find($orderData['shop_order_id']));
//                }

            } catch (\Exception $e) {
                Log::error('TBC callback error: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                ]);
            }

        }
    }
}
