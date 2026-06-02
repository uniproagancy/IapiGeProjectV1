<?php

namespace App\Livewire\Web\Checkout;

use App\Models\Order\Order;
use App\Models\Order\OrderDelivery;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderTransaction;
use App\Models\Payments\Payment;
use App\Models\Product\Product;
use App\Models\User\User;
use App\Services\Payments\BOGPayment;
use App\Services\Facebook\FacebookPixelService;
use Giorgijorji\LaravelTbcInstallment\LaravelTbcInstallment;
use App\Traits\WithCart;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Exception;

class Checkout extends Component
{
    use WithCart;

    // ============================================
    // Properties
    // ============================================

    // Customer Info
    public $name        = '';
    public $lastname    = '';
    public $email       = '';
    public $phone       = '';
    public $verify_phone = '';
    public $address     = '';
    public $comment     = '';

    // Delivery
    public $shipping_cost = 0;

    // Payment
    public $payment_id = '';

    // Direct Purchase
    #[Url(as: 'product_id')]
    public $product_id = null;

    #[Url(as: 'quantity')]
    public $quantity = 1;

    public $orderItems  = [];
    public $subtotal    = 0;
    public $total       = 0;
    public $tax         = 0;

    public string $checkoutEventId = '';

    // ============================================
    // Lifecycle Hooks
    // ============================================

    public function mount(): void
    {
        try {
            // ✅ #[Url] პროპერტი mount()-ში შეიძლება ჯერ არ იყოს set,
            // ამიტომ request()-იდანაც ვკითხულობთ
            $productId = $this->product_id ?? request('product_id');

            if (!empty($productId)) {
                $this->product_id = $productId;
            }

            if (count(Cart::getContent()) > 0 || !empty($this->product_id)) {
                $this->loadOrderItems();
                $this->calculateTotals();
            } else {
                $this->redirect(route('web.products.index'));
                return;
            }

            if ($this->quantity < 1) {
                $this->quantity = 1;
            }

            if (auth()->check()) {
                $user               = auth()->user();
                $this->name         = $user->name ?? '';
                $this->lastname     = $user->lastname ?? '';
                $this->email        = $user->email;
                $this->phone        = $user->phone ?? '';
                $this->verify_phone = $user->verify_phone ?? '';
            }

            $this->trackInitiateCheckout();

        } catch (Exception $e) {
            Log::error('Checkout mount error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'გვერდის ჩატვირთვა ვერ მოხერხდა');
        }
    }

    // ============================================
    // Load Order Items
    // ============================================

    public function loadOrderItems(): void
    {
        try {
            if ($this->product_id) {
                Log::warning('product load from url');
                $product = Product::with(['translations', 'price'])
                    ->where('active', 1)
                    ->where('show', 1)
                    ->findOrFail($this->product_id);

                $translation = $product->translation(app()->getLocale()) ?? $product->translation('ka');
                $price       = !empty($product->price?->discount_price)
                    ? $product->price->discount_price
                    : $product->price?->regular_price;

                // ✅ plain array
                $this->orderItems = [
                    [
                        'id'       => $product->id,
                        'name'     => $translation->title,
                        'sku'      => $product->sku,
                        'image'    => $product->main_image,
                        'price'    => $price,
                        'quantity' => $this->quantity,
                        'total'    => $price * $this->quantity,
                    ]
                ];

            } else {
                Log::warning('product load from cart');
                $this->loadCartFromDatabase();
                $cartItems = Cart::getContent();

                if ($cartItems->isEmpty()) {
                    $this->dispatch('ui:error', message: 'თქვენი კალათა ცარიელია');
                    $this->redirect(route('web.main.index'));
                    return;
                }

                // ✅ plain array
                $this->orderItems = $cartItems->map(function ($item) {
                    return [
                        'id'       => $item->id,
                        'name'     => $item->name,
                        'sku'      => $item->attributes->sku ?? null,
                        'image'    => $item->attributes->image ?? null,
                        'price'    => $item->price,
                        'quantity' => $item->quantity,
                        'total'    => $item->getPriceSum(),
                    ];
                })->values()->toArray();
            }

        } catch (Exception $e) {
            Log::error('Error loading order items: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'ჩვენების შეცდომა');
            $this->redirect(route('web.main.index'));
        }
    }

    // ============================================
    // Calculate Totals
    // ============================================

    public function calculateTotals(): void
    {
        try {
            // ✅ collect() — plain array-ც მიიღება
            $this->subtotal = collect($this->orderItems)->sum('total');
            $this->tax      = 0;
            $this->total    = $this->subtotal + $this->tax;
        } catch (Exception $e) {
            Log::error('Error calculating totals: ' . $e->getMessage());
        }
    }

    // ============================================
    // Facebook Pixel - InitiateCheckout
    // ============================================

    private function trackInitiateCheckout(): void
    {
        try {
            $this->checkoutEventId = 'ic_' . time() . '_' . Str::random(6);

            $items = [];
            foreach ($this->orderItems as $item) {
                $items[] = [
                    'id'       => $item['id'],
                    'quantity' => $item['quantity'],
                ];
            }

            app(FacebookPixelService::class)->trackCheckout(
                value: $this->total,
                currency: 'GEL',
                items: $items,
                params: [],
                eventId: $this->checkoutEventId
            );

            Log::info('✅ InitiateCheckout tracked', [
                'event_id'    => $this->checkoutEventId,
                'total'       => $this->total,
                'items_count' => count($items),
            ]);

        } catch (Exception $e) {
            Log::warning('Facebook Pixel InitiateCheckout error: ' . $e->getMessage());
        }
    }

    // ============================================
    // Place Order
    // ============================================

    public function placeOrder(): void
    {
        try {
            $this->validate([
                'address'    => 'required|string|max:255',
                'payment_id' => 'required',
            ], [
                'address.required'    => 'მისამართი აუცილებელია',
                'address.min'         => 'მისამართი უნდა იყოს მინიმუმ 5 სიმბოლოსი',
                'payment_id.required' => 'გადახდის მეთოდი აუცილებელია',
            ]);

            if (Auth::check()) {
                $this->placeAuthenticatedOrder();
            } else {
                $this->placeGuestOrder();
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errorField = array_key_first($e->errors());
            $this->dispatch('scrollToError', field: $errorField);
            $this->dispatch('ui:error', message: 'გთხოვთ შეამოწმეთ ფორმა');
        } catch (Exception $e) {
            Log::error('Place order error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეკვეთის შექმნა ვერ მოხერხდა. სცადეთ ისევ.');
        }
    }

    // ============================================
    // Authenticated Order
    // ============================================

    private function placeAuthenticatedOrder(): void
    {
        $order = Order::create([
            'user_id'         => Auth::id(),
            'payment_id'      => $this->payment_id,
            'comment'         => $this->comment,
            'created_by'      => Auth::id(),
            'delivery_amount' => 0,
            'amount'          => round($this->subtotal),
        ]);

        foreach ($this->orderItems as $orderItem) {
            OrderItem::create([
                'product_id' => $orderItem['id'],
                'quantity'   => $orderItem['quantity'],
                'price'      => round($orderItem['price']),
                'order_id'   => $order->id,
            ]);
        }

        OrderDelivery::create([
            'order_id' => $order->id,
            'address'  => $this->address,
        ]);

        (new \App\Services\Sender\SmsOffice)->send(
            Auth::user()->phone,
            'თქვენი შეკვეთა მიღებულია, შეკვეთის ნომერი ' . $order->id . ' ჩვენი ოპერატორი მალე დაგიკავშირდებათ!'
        );
        (new \App\Services\Sender\SmsOffice)->send(
            555700720,
            'შემოვიდა ახალი შეკვეთა, შეკვეთის ნომერი ' . $order->id
        );

        $this->trackLead($order, isGuest: false);
        $this->processPayment($order);
    }

    // ============================================
    // Guest Order
    // ============================================

    private function placeGuestOrder(): void
    {
        $this->validate([
            'name'     => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email'    => 'nullable|email',
            'phone'    => 'required|string',
        ], [
            'name.required'     => 'სახელი აუცილებელია',
            'lastname.required' => 'გვარი აუცილებელია',
            'email.email'       => 'ელ.ფოსტა არასწორია',
            'phone.required'    => 'ტელეფონი აუცილებელია',
        ]);

        $product     = Product::with(['translations', 'price'])->findOrFail($this->product_id);
        $translation = $product->translation(app()->getLocale()) ?? $product->translation('ka');

        $price = ($product->price->discount_price > 0)
            ? $product->price->discount_price
            : $product->price->regular_price;

        if (!empty($this->email)) {
            $user = User::updateOrCreate(
                ['email' => $this->email],
                [
                    'name'     => $this->name,
                    'lastname' => $this->lastname,
                    'phone'    => $this->phone,
                ]
            );
        } else {
            $user = User::create([
                'name'     => $this->name,
                'lastname' => $this->lastname,
                'phone'    => $this->phone,
                'email'    => null,
            ]);
        }

        $order = Order::create([
            'user_id'         => $user->id,
            'payment_id'      => $this->payment_id,
            'comment'         => $this->comment,
            'created_by'      => $user->id,
            'delivery_amount' => 0,
            'amount'          => round($this->subtotal),
        ]);

        OrderItem::create([
            'product_id' => $product->id,
            'quantity'   => $this->quantity,
            'price'      => round($price),
            'order_id'   => $order->id,
        ]);

        OrderDelivery::create([
            'order_id' => $order->id,
            'address'  => $this->address,
        ]);

        (new \App\Services\Sender\SmsOffice)->send(
            $this->phone,
            'თქვენი შეკვეთა მიღებულია, შეკვეთის ნომერი ' . $order->id . ' ჩვენი ოპერატორი მალე დაგიკავშირდებათ!'
        );

        $addToCartEventId = 'atc_' . time() . '_' . Str::random(6);
        app(FacebookPixelService::class)->trackAddToCart(
            product: [
                'id'       => $product->id,
                'name'     => $translation->title,
                'quantity' => $this->quantity,
            ],
            value: $price * $this->quantity,
            currency: 'GEL',
            params: [],
            eventId: $addToCartEventId
        );

        Log::info('✅ AddToCart tracked (Quick Checkout)', [
            'event_id'     => $addToCartEventId,
            'product_id'   => $product->id,
            'product_name' => $translation->title,
            'quantity'     => $this->quantity,
            'value'        => $price * $this->quantity,
        ]);

        Cart::clear();
        $this->trackLead($order, isGuest: true);
        $this->processPayment($order);
    }

    // ============================================
    // Facebook Pixel - Lead
    // ============================================

    private function trackLead(Order $order, bool $isGuest = false): void
    {
        try {
            $leadEventId     = 'lead_' . time() . '_' . Str::random(6);
            $contentCategory = $isGuest ? 'quick_checkout' : 'checkout';

            $userData = $isGuest
                ? [
                    'email'      => $this->email,
                    'phone'      => $this->phone,
                    'first_name' => $this->name,
                    'last_name'  => $this->lastname,
                ]
                : [
                    'email'      => auth()->user()->email,
                    'phone'      => auth()->user()->phone,
                    'first_name' => auth()->user()->name,
                    'last_name'  => auth()->user()->lastname,
                ];

            $contents     = [];
            $contentIds   = [];
            $contentNames = [];

            $orderItems = OrderItem::with(['product.translations', 'product.price'])
                ->where('order_id', $order->id)
                ->get();

            foreach ($orderItems as $orderItem) {
                $product = $orderItem->product;
                if (!$product) {
                    continue;
                }

                $translation    = $product->translation(app()->getLocale()) ?? $product->translation('ka');
                $contents[]     = [
                    'id'         => $product->id,
                    'quantity'   => $orderItem->quantity,
                    'item_price' => $orderItem->price,
                ];
                $contentIds[]   = $product->id;
                $contentNames[] = $translation->title ?? 'Product #' . $product->id;
            }

            app(FacebookPixelService::class)->trackLead(
                userData: $userData,
                customData: [
                    'value'            => $order->amount,
                    'currency'         => 'GEL',
                    'content_category' => $contentCategory,
                    'content_type'     => 'product',
                    'contents'         => $contents,
                    'content_ids'      => $contentIds,
                    'content_name'     => implode(', ', array_slice($contentNames, 0, 3)),
                    'num_items'        => count($contents),
                ],
                eventId: $leadEventId
            );

            $this->dispatch('pixel:lead',
                value: $order->amount,
                num_items: count($contents),
                event_id: $leadEventId
            );

            Log::info('✅ Facebook Pixel Lead tracked', [
                'order_id'       => $order->id,
                'event_id'       => $leadEventId,
                'is_guest'       => $isGuest,
                'amount'         => $order->amount,
                'products_count' => count($contents),
            ]);

        } catch (Exception $e) {
            Log::error('Facebook Pixel Lead error: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null,
                'trace'    => $e->getTraceAsString(),
            ]);
        }
    }

    // ============================================
    // Process Payment
    // ============================================

    private function processPayment(Order $order): void
    {
        try {
            switch ($this->payment_id) {
                case '3':
                    $this->redirect((new BOGPayment)->createPaymentOrder($order));
                    break;

                case '4':
                    if ($order->amount < 100) {
                        $this->dispatch('ui:error', message: 'განვადების თანხა უნდა აღემატებოდეს 100 ლარს');
                    } else {
                        $this->dispatch('bog:installment',
                            amount: $order->amount + ($order->amount * 0.05),
                            url: route('bog.create-installment-order', $order->id)
                        );
                    }
                    break;

                case '5':
                    if ($order->amount < 100) {
                        $this->dispatch('ui:error', message: 'ნაწილ-ნაწილ თანხა უნდა აღემატებოდეს 100 ლარს!');
                    } else {
                        $this->dispatch('bog:installment-part',
                            amount: $order->amount,
                            url: route('bog.create-part-installment-order', $order->id)
                        );
                    }
                    break;

                case '7':
                    if ($order->amount < 150) {
                        $this->dispatch('ui:error', message: 'TBC განვადების თანხა უნდა აღემატებოდეს 150 ლარს!');
                    } else {
                        $tbcInstallment = new LaravelTbcInstallment();
                        $products       = [];

                        foreach ($order->items as $item) {
                            $products[] = [
                                'name'     => $item->product->translation('ka')->title,
                                'price'    => round($item->price + ($item->price * 0.05)) * $item->quantity,
                                'quantity' => $item->quantity,
                            ];
                        }

                        $tbcInstallment->addProducts($products);
                        $response = $tbcInstallment->applyInstallmentApplication(
                            $order->id,
                            round($order->amount + ($order->amount * 0.05))
                        );
                        if ($response['status_code'] === 200) {
                            OrderTransaction::create([
                                'order_id'         => $order->id,
                                'payment_order_id' => $tbcInstallment->getSessionId(),
                                'url'              => $tbcInstallment->getRedirectUri(),
                                'amount'           => $order->amount,
                                'status'           => 1,
                                'type'             => 'tbc_installment',
                            ]);
                            $this->redirect($tbcInstallment->getRedirectUri());
                        }
                    }
                    break;

                case '9':
                    if ($order->amount < 150) {
                        $this->dispatch('ui:error', message: 'კრედო განვადების თანხა უნდა აღემატებოდეს 150 ლარს!');
                    } else {
                        $this->redirect(route('credo-create-order', ['order_id' => $order->id]));
                    }
                    break;

                case '2':
                    $this->dispatch('ui:error', message: 'შეკვეთა მიღებულია!');
                    $this->redirect('/checkout/success');
                    break;

                default:
                    $this->redirect('/checkout/success');
                    break;
            }

        } catch (Exception $e) {
            Log::error('Payment processing error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'გადახდის დამუშავება ვერ მოხერხდა');
        }
    }

    // ============================================
    // Render
    // ============================================

    public function render()
    {
        return view('livewire.web.cart.checkout', [
            'payment_list'      => Payment::where('active', 1)->orderBy('sortable', 'ASC')->get(),
            'checkout_event_id' => $this->checkoutEventId ?? null,
            'total'             => $this->total,
            'orderItemsCount'   => count($this->orderItems ?? []),
        ])->layout('livewire.web.layout');
    }
}