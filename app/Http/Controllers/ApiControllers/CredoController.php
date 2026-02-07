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
                    'id' => $item->product_id,
                    'title' => $item->product->translation('ka')->title,
                    'amount' => $item->quantity,
                    'price' => ($item->price + ($item->price * 0.1)) * 100, // თეთრებში
                    'type' => '0',
                ];
            }
            $checkString = ' ';
            foreach ($products as $p) {
                $checkString .= $p['id'] . $p['title'] . $p['amount'] . $p['price'] . $p['type'];
            }
            $check = md5($checkString);
            $payload = [
                'merchantId' => '12545',
                'orderCode'  => $order->id,
                'check'      => $check,
                'products'   => $products,
            ];
            $data = json_encode($payload);
            return view('credo', ['data' => $data]);
        }
    }
}
