<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\OrderTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BOGPaymentController extends Controller
{
    //
    public function callback(Request $request)
    {
        if(!empty($request)) {
            $transaction = OrderTransaction::where('payment_order_id', $request->body['order_id'])->first();
            $transaction->update(['response' => $request]);
            if(!empty($transaction)){
                $order = Order::where('id', $request->body['order_id'])->first();
                if(!empty($order)){
                    if($request->body['payment_status_id']['key'] === 'completed'){
                        $order->update(['payment_status' => 2]);
                    }
                }
            }
        }
    }
}
