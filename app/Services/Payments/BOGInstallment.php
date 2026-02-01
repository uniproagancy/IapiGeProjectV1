<?php

namespace App\Services\Payments;

use App\Models\Order\OrderTransaction;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BOGInstallment
{
    private string $clientId;
    private string $clientSecret;
    private string $orderUrl;
    private string $tokenUrl;
    private string $installmentSuccess;
    private string $installmentFail;
    private string $installmentReject;

    protected float $handlingPee;

    public function __construct()
    {
        $this->clientId = Config::get('bog.installment.public_key');
        $this->clientSecret = Config::get('bog.installment.secret_key');
        $this->orderUrl = Config::get('bog.installment.order_url');
        $this->tokenUrl = Config::get('bog.token_url');
        $this->installmentSuccess = Config::get('bog.installment.success_url');
        $this->installmentFail = Config::get('bog.installment.fail_url');
        $this->installmentReject = Config::get('bog.installment.reject_url');
        $this->handlingPee = 0.05;
    }

    public function getToken()
    {
        $token = Http::asForm()->withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ])->post($this->tokenUrl, ['grant_type' => 'client_credentials']);
        return $token->json()['access_token'];
    }

    public function createInstallmentOrder($order, $installment_month)
    {
        $cartItems = [];
        foreach ($order->items as $item) {
            $cartItems[] = [
                'total_item_amount' => ($item->price + ($item->price * $this->handlingPee)) * $item->quantity,
                'item_description' => $item->product->translation('ka')->title,
                'total_item_qty' => $item->quantity,
                'item_vendor_code' => $item->product->sku ?? $item->product->id,
                'product_image_url' => asset('storage/' . $item->product->main_image),
                'item_site_detail_url' => route('web.products.view', $item->product->translations->where('locale', 'ka')->first()->slug)
            ];
        }
        $payload = [
            'intent' => 'LOAN',
            'installment_month' => $installment_month,
            'installment_type' => 'STANDARD',
            'shop_order_id' => $order->id,
            'success_redirect_url' => 'https://iapi.ge/bog/installment/redirect?status=1',
            'fail_redirect_url' => 'https://iapi.ge/bog/installment/redirect?status=2',
            'reject_redirect_url' => 'https://iapi.ge/bog/installment/redirect?status=3',
            'validate_items' => true,
            'locale' => 'ka',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'GEL',
                        'value' => $order->amount + ($order->amount * $this->handlingPee),
                    ]
                ]
            ],
            'cart_items' => $cartItems,
        ];
        $create_order = Http::withToken($this->getToken())
            ->post($this->orderUrl, $payload)
            ->json();
        if ($create_order['status'] === 'CREATED') {
            OrderTransaction::create([
                'order_id' => $order->id,
                'payment_order_id' => $create_order['order_id'],
                'url' => $create_order['links'][1]['href'],
                'amount' => $order->amount,
                'status' => 1,
                'type' => 'installment',
            ]);
        }
        return [
            'redirectUrl' => $create_order['links'][1]['href'],
            'orderId' => $create_order['order_id'],
        ];
    }

    public function createPartInstallmentOrder($order, $installment_month)
    {
        $cartItems = [];
        foreach ($order->items as $item) {
            $cartItems[] = [
                'total_item_amount' => $item->price * $item->quantity,
                'item_description' => $item->product->translation('ka')->title,
                'total_item_qty' => $item->quantity,
                'item_vendor_code' => $item->product->sku ?? $item->product->id,
                'product_image_url' => asset('storage/' . $item->product->main_image),
                'item_site_detail_url' => route('web.products.view', $item->product->translations->where('locale', 'ka')->first()->slug)
            ];
        }
        $payload = [
            'intent' => 'LOAN',
            'installment_month' => $installment_month,
            'installment_type' => 'ZERO',
            'shop_order_id' => $order->id,
            'success_redirect_url' => $this->installmentSuccess,
            'fail_redirect_url' => $this->installmentFail,
            'reject_redirect_url' => $this->installmentReject,
            'validate_items' => true,
            'locale' => 'ka',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'GEL',
                        'value' => $order->amount,
                    ]
                ]
            ],
            'cart_items' => $cartItems,
        ];
        $create_order = Http::withToken($this->getToken())
            ->post($this->orderUrl, $payload)->json();
        if ($create_order['status'] === 'CREATED') {
            OrderTransaction::create([
                'order_id' => $order->id,
                'payment_order_id' => $create_order['order_id'],
                'url' => $create_order['links'][1]['href'],
                'amount' => $order->amount,
                'status' => 1,
                'type' => 'part-installment',
            ]);
        }
        return [
            'redirectUrl' => $create_order['links'][1]['href'],
            'orderId' => $create_order['order_id'],
        ];
    }

    public function installmentRedirect(Request $request)
    {
        Log::info($request);
    }
}