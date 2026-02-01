<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use Illuminate\Http\Request;

class CredoController extends Controller
{
    //
    public function createOrder(Request $request)
    {
        if(!empty($request->order_id)) {
            $order = Order::find($request->order_id);
            $products = [];
            foreach ($order->items as $item) {
                $products[] = [
                    'id' => $item->id,
                    'title' => 'PHILIPS HP6549/00',
                    'amount' => $item->quantity,
                    'price' => $item->price + ($item->price * 0.1), // თეთრებში
                    'type' => '0',
                ];
            }
            $checkString = '12545';
            foreach ($products as $p) {
                $checkString .= $p['id'] . $p['title'] . $p['amount'] . $p['price'] . $p['type'];
            }
            $check = md5($checkString);
            $payload = [
                'merchantId' => '21400',
                'orderCode'  => '17407',
                'check'      => $check,
                'products'   => $products,
            ];
            $data = json_encode($payload);
//            dd($data);
            return view('credo', ['data' => $data]);
        }
    }
}
