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

    public function status() {

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

                $client = new \GuzzleHttp\Client();

                $response = $client->request('GET', 'https://api.tbcbank.ge/v1/online-installments/applications/%7BsessionId%7D/status', [
                    'body' => '{"merchantKey":"416353635-82138e94-8cd4-4553-98ef-195f7dfdbe3d"}',
                    'headers' => [
                        'accept' => 'application/json',
                        'content-type' => 'application/json',
                    ],
                ]);

                print_r($response->getBody());
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
                Log::error('BOGInstallment callback error: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                ]);
            }

        }
    }
}
