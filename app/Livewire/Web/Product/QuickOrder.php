<?php

namespace App\Livewire\Web\Product;

use App\Models\Order\Order;
use App\Models\Order\OrderDelivery;
use App\Models\Order\OrderItem;
use App\Models\Product\Product;
use App\Models\User\User;
use App\Services\Facebook\FacebookPixelService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Exception;

class QuickOrder extends Component
{
    public int $productId;
    public int $quantity = 1;

    #[Validate('required|string|max:255', message: 'სახელი და გვარი აუცილებელია')]
    public string $name = '';

    #[Validate('required|string|min:9|max:20', message: 'ტელეფონის ნომერი აუცილებელია')]
    public string $phone = '';

    public string $delivery = 'courier';
    public string $comment = '';
    public bool $success = false;

    public float $orderAmount = 0;
    public string $leadEventId = '';

    // ✅ სახელი და გვარი explode-ით
    private function parseName(): array
    {
        $parts     = explode(' ', trim($this->name), 2);
        $firstName = $parts[0] ?? '';
        $lastName  = $parts[1] ?? '';
        return [$firstName, $lastName];
    }

    public function placeOrder(): void
    {
        $this->validate();

        try {
            $product = Product::with(['translations', 'price'])->findOrFail($this->productId);

            $price = !empty($product->price->discount_price)
                ? $product->price->discount_price
                : $product->price->regular_price;

            [$firstName, $lastName] = $this->parseName();

            $user = User::create([
                'name'     => $firstName,
                'lastname' => $lastName,
                'phone'    => $this->phone,
                'email'    => null,
            ]);

            $deliveryPaymentMap = [
                'courier'     => 10,
                'installment' => 11,
                'bank'        => 12,
                'card'        => 13,
            ];

            $order = Order::create([
                'user_id'         => $user->id,
                'payment_id'      => $deliveryPaymentMap[$this->delivery] ?? 10,
                'comment'         => $this->comment,
                'created_by'      => $user->id,
                'delivery_amount' => 0,
                'amount'          => round($price * $this->quantity),
            ]);

            OrderItem::create([
                'product_id' => $product->id,
                'quantity'   => $this->quantity,
                'price'      => round($price),
                'order_id'   => $order->id,
            ]);

            OrderDelivery::create([
                'order_id' => $order->id,
                'address'  => '',
            ]);

            (new \App\Services\Sender\SmsOffice)->send(
                $this->phone,
                'თქვენი შეკვეთა მიღებულია, შეკვეთის ნომერი ' . $order->id . '. ჩვენი ოპერატორი მალე დაგიკავშირდებათ!'
            );

            (new \App\Services\Sender\SmsOffice)->send(
                555700720,
                'სწრაფი შეკვეთა #' . $order->id . ' — ' . $this->name . ' — ' . $this->phone . ' — ' . $this->delivery
            );

            $this->orderAmount = $order->amount;
            $this->leadEventId = 'lead_' . time() . '_' . Str::random(6);

            $this->trackLead($order, $product);
            $this->success = true;

        } catch (Exception $e) {
            Log::error('QuickOrder error: ' . $e->getMessage());
            $this->addError('phone', 'შეცდომა მოხდა, სცადეთ ისევ');
        }
    }

    private function trackLead(Order $order, Product $product): void
    {
        try {
            [$firstName, $lastName] = $this->parseName();
            $translation = $product->translation(app()->getLocale()) ?? $product->translation('ka');

            $pixelService = app(\App\Services\Facebook\FacebookPixelService::class);
            $pixelService->trackLead(
                userData: [
                    'phone'      => $this->phone,
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                ],
                customData: [
                    'value'            => $order->amount,
                    'currency'         => 'GEL',
                    'content_category' => 'quick_order',
                    'content_type'     => 'product',
                    'contents'         => [[
                        'id'         => $product->id,
                        'quantity'   => $this->quantity,
                        'item_price' => $order->amount / $this->quantity,
                    ]],
                    'content_ids'      => [$product->id],
                    'content_name'     => $translation->title ?? '',
                    'num_items'        => 1,
                ],
                eventId: $this->leadEventId
            );

            // ✅ pixel data ბაზაში შენახვა — Purchase-ისთვის გამოვიყენებთ
            $pixelService->savePixelData($order->id, $this->leadEventId, [
                'phone'      => $this->phone,
                'first_name' => $firstName,
                'last_name'  => $lastName,
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('QuickOrder Lead pixel error: ' . $e->getMessage());
        }
    }
    public function render()
    {
        $product = Product::with(['price'])->findOrFail($this->productId);
        return view('livewire.web.product.quick-order', compact('product'));
    }
}