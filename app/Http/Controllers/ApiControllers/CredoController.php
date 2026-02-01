<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CredoController extends Controller
{
    //
    public function createOrder()
    {
        $products = [
            [
                'id' => '4634',
                'title' => 'PHILIPS HP6549/00',
                'amount' => '2',
                'price' => '21400', // თეთრებში
                'type' => '0',
            ]
        ];
        $checkString = '';
        foreach ($products as $p) {
            $checkString .= $p['id'] . $p['title'] . $p['amount'] . $p['price'] . $p['type'];
        }
        $check = md5($checkString);
        $payload = [
            'merchantId' => '',
            'orderCode'  => '17407',
            'check'      => $check,
            'products'   => $products,
        ];
        $data = json_encode($payload, JSON_UNESCAPED_UNICODE);
        return view('views.credo', $data);
    }
}
