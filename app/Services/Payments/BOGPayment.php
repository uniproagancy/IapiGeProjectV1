<?php

namespace App\Services\Payments;

use App\Models\Order\OrderTransaction;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class BOGPayment
{

    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
//        $this->clientId = Config::get('bog.payment.public_key');
//        $this->clientSecret = Config::get('bog.payment.secret_key');
    }

    public function getToken()
    {
        $token = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic ' . base64_encode('10003075' . ':' . 'sziq796zJImm')
            ])
            ->post('https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token', [
                'grant_type' => 'client_credentials'
            ]);
        return $token->json()['access_token'];
    }

    public function createPaymentOrder($order)
    {
        $basket = [];
        foreach ($order->items as $item) {
            $basket[] = [
                'quantity' => $item->quantity,
                'unit_price' => 0.1,
                'product_id' => $item->product->id,
            ];
        }
        $payload = [
            'callback_url' => 'https://iapi.ge/bog/api/payment/callback',
            'external_order_id' => $order->id,
            'purchase_units' => [
                'currency' => 'GEL',
                'total_amount' => 0.1 + $order->delivery_amount,
                'basket' => $basket
            ],
            'redirect_urls' => [
                'fail' => 'https://iapi.ge/payment/fail',
                'success' => 'https://iapi.ge/payment/success',
            ]
        ];
        $create_order = Http::withToken($this->getToken())
            ->post("https://api.bog.ge/payments/v1/ecommerce/orders", $payload)->json();
        if ($create_order) {
            OrderTransaction::create([
                'order_id' => $order->id,
                'payment_order_id' => $create_order['id'],
                'url' => $create_order['_links']['redirect']['href'],
                'amount' => $order->amount,
                'status' => 1,
                'type' => 'payment',
            ]);
        }
        return $create_order['_links']['redirect']['href'];
    }

}