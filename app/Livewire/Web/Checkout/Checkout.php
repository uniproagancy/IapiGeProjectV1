<?php

namespace App\Livewire\Web\Checkout;

use App\Models\Order\Order;
use App\Models\Order\OrderDelivery;
use App\Models\Order\OrderItem;
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
use Livewire\Attributes\Url;
use Livewire\Component;
use Exception;

class Checkout extends Component
{
    use WithCart;

    // Customer Info
    public $name = '';
    public $lastname = '';
    public $email = '';
    public $phone = '';
    public $verify_phone = '';
    public $address = '';
    public $comment = '';

    // Delivery
    public $shipping_cost = 0;

    // Payment
    public $payment_id = '';

    // Direct Purchase
    #[Url(as: 'product_id')]
    public $product_id = null;

    #[Url(as: 'quantity')]
    public $quantity = 1;

    public $orderItems = [];
    public $subtotal = 0;
    public $total = 0;

    public function mount()
    {
        try {
            if (count(Cart::getContent()) > 0 or !empty($this->product_id)) {
                $this->loadOrderItems();
                $this->calculateTotals();
            } else {
                return $this->redirect(route('web.products.index'));
            }

            if ($this->quantity < 1) {
                $this->quantity = 1;
            }

            if (auth()->check()) {
                $user = auth()->user();
                $this->name = $user->name ?? '';
                $this->lastname = $user->lastname ?? '';
                $this->email = $user->email;
                $this->phone = $user->phone ?? '';
                $this->verify_phone = $user->verify_phone ?? '';
            }

            // ✅ Facebook Pixel - InitiateCheckout Event
            $this->trackInitiateCheckout();

        } catch (Exception $e) {
            Log::error('Checkout mount error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'გვერდის ჩატვირთვა ვერ მოხერხდა');
        }
    }

    public function loadOrderItems()
    {
        try {
            if ($this->product_id) {
                $product = Product::with(['translations', 'price'])
                    ->where('active', 1)
                    ->where('show', 1)
                    ->findOrFail($this->product_id);

                if ($product->in_stock !== 1) {
                    $this->dispatch('ui:error', message: 'პროდუქტი არ არის მარაგში', type: 'error');
                    return $this->redirect(route('web.products.index'));
                }

                $translation = $product->translation(app()->getLocale()) ?? $product->translation('ka');
                $price = $product->price->discount_price ?? $product->price->regular_price;

                $this->orderItems = collect([
                    [
                        'id' => $product->id,
                        'name' => $translation->title,
                        'sku' => $product->sku,
                        'image' => $product->main_image,
                        'price' => $price,
                        'quantity' => $this->quantity,
                        'total' => $price * $this->quantity,
                    ]
                ]);
            } else {
                $this->loadCartFromDatabase();
                $cartItems = Cart::getContent();

                if ($cartItems->isEmpty()) {
                    $this->dispatch('ui:error', message: 'თქვენი კალათა ცარიელია');
                    return $this->redirect(route('web.main.index'));
                }

                $this->orderItems = $cartItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'sku' => $item->attributes->sku ?? null,
                        'image' => $item->attributes->image ?? null,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'total' => $item->getPriceSum(),
                    ];
                });
            }
        } catch (Exception $e) {
            Log::error('Error loading order items: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'ჩვენების ошибка');
            return redirect()->route('web.main.index');
        }
    }

    public function calculateTotals()
    {
        try {
            $this->subtotal = $this->orderItems->sum('total');
            $this->tax = 0;
            $this->total = $this->subtotal + $this->tax;
        } catch (Exception $e) {
            Log::error('Error calculating totals: ' . $e->getMessage());
        }
    }

    /**
     * ✅ Track InitiateCheckout Event
     */
    private function trackInitiateCheckout()
    {
        try {
            $items = [];
            foreach ($this->orderItems as $item) {
                $items[] = [
                    'id' => $item['id'],
                    'quantity' => $item['quantity'],
                ];
            }

            app(FacebookPixelService::class)->trackCheckout(
                value: $this->total,
                currency: 'GEL',
                items: $items
            );

        } catch (Exception $e) {
            Log::warning('Facebook Pixel InitiateCheckout error: ' . $e->getMessage());
        }
    }

    /**
     * ✅ Place order with validation and scroll to error
     */
    public function placeOrder()
    {
        try {
            // ✅ Validation rules
            $validated = $this->validate([
                'address' => 'required|string|max:255',
                'payment_id' => 'required',
            ], [
                'address.required' => 'მისამართი აუცილებელია',
                'address.min' => 'მისამართი უნდა იყოს მინიმუმ 5 სიმბოლოსი',
                'payment_id.required' => 'გადახდის მეთოდი აუცილებელია',
            ]);

            // ✅ თუ ავტორიზებულია
            if (Auth::check()) {
                $order = Order::create([
                    'user_id' => Auth::user()->id,
                    'payment_id' => $this->payment_id,
                    'comment' => $this->comment,
                    'created_by' => Auth::user()->id,
                    'delivery_amount' => 0,
                    'amount' => $this->subtotal,
                ]);

                foreach ($this->orderItems as $orderItem) {
                    OrderItem::create([
                        'product_id' => $orderItem['id'],
                        'quantity' => $orderItem['quantity'],
                        'price' => $orderItem['price'],
                        'order_id' => $order->id,
                    ]);
                }

                OrderDelivery::create([
                    'order_id' => $order->id,
                    'address' => $this->address,
                ]);

                // ✅ Facebook Pixel - Lead (ავტორიზებული)
                $this->trackLead($order, isGuest: false);

                $this->processPayment($order);
            }
            // ✅ თუ არაავტორიზებული (სწრაფი შეძენა)
            else {
                // Validation for guest users
                $this->validate([
                    'name' => 'required|string|max:255',
                    'lastname' => 'required|string|max:255',
                    'email' => 'email',
                    'phone' => 'required|string',
                ], [
                    'name.required' => 'სახელი აუცილებელია',
                    'lastname.required' => 'გვარი აუცილებელია',
                    'email.required' => 'ელ.ფოსტა აუცილებელია',
                    'email.email' => 'ელ.ფოსტა არასწორია',
                    'phone.required' => 'ტელეფონი აუცილებელია',
                ]);

                $product = Product::find($this->product_id);
                $price = $product->price->discount_price ?? $product->price->regular_price;

//                if(!empty($this->email)) {
//
//                }
                $check_user = User::where('email', $this->email)->first();
                if($check_user){
                    $check_user->update(['email' => $this->email]);
                }
                $user = User::create([
                    'name' => $this->name,
                    'lastname' => $this->lastname,
                    'email' => $this->email,
                    'phone' => $this->phone,
                ]);

                $order = Order::create([
                    'user_id' => $user->id,
                    'payment_id' => $this->payment_id,
                    'comment' => $this->comment,
                    'created_by' => $user->id,
                    'delivery_amount' => 0,
                    'amount' => $this->subtotal,
                ]);

                OrderItem::create([
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => $price,
                    'order_id' => $order->id,
                ]);

                OrderDelivery::create([
                    'order_id' => $order->id,
                    'address' => $this->address,
                ]);

                // ✅ Facebook Pixel - Lead (არაავტორიზებული)
                $this->trackLead($order, isGuest: true);

                $this->processPayment($order);
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

    /**
     * ✅ Track Lead - Universal (Order Items-დან პროდუქტები)
     */
    private function trackLead($order, $isGuest = false)
    {
        try {
            $userData = [];
            $contentCategory = $isGuest ? 'quick_checkout' : 'checkout';

            // ✅ თუ Guest User - ფორმის მონაცემები
            if ($isGuest) {
                $userData = [
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'first_name' => $this->name,
                    'last_name' => $this->lastname,
                ];
            }
            // ✅ თუ Authorized - Test Mode-ისთვის explicit data
            else {
                if (config('app.env') !== 'production') {
                    $userData = [
                        'email' => auth()->user()->email,
                        'phone' => auth()->user()->phone,
                        'first_name' => auth()->user()->name,
                        'last_name' => auth()->user()->lastname,
                    ];
                }
            }

            // ✅ პროდუქტების ინფორმაცია - Order Items-დან (უკვე შენახულია Database-ში)
            $contents = [];
            $contentIds = [];
            $contentNames = [];

            // Load fresh order items with product relations
            $orderItems = OrderItem::with('product.translations', 'product.price')
                ->where('order_id', $order->id)
                ->get();

            foreach ($orderItems as $orderItem) {
                $product = $orderItem->product;
                if ($product) {
                    $translation = $product->translation(app()->getLocale()) ?? $product->translation('ka');

                    $contents[] = [
                        'id' => $product->id,
                        'quantity' => $orderItem->quantity,
                        'item_price' => $orderItem->price,
                    ];
                    $contentIds[] = $product->id;
                    $contentNames[] = $translation->title ?? 'Product #' . $product->id;
                }
            }

            $customData = [
                'value' => $order->amount,
                'currency' => 'GEL',
                'content_category' => $contentCategory,
                'content_type' => 'product',
                'contents' => $contents,
                'content_ids' => $contentIds,
                'content_name' => implode(', ', array_slice($contentNames, 0, 3)),
                'num_items' => count($contents),
            ];

            // ✅ Production
            app(FacebookPixelService::class)->trackLead(
                userData: $userData,
                customData: $customData
            );
            // ✅ Test/Local/Staging
//            app(FacebookPixelService::class)->trackLeadWithTest(
//                testCode: config('services.facebook.test_event_code', 'TEST98776'),
//                userData: $userData,
//                customData: $customData
//            );

            Log::info('✅ Facebook Pixel Lead tracked', [
                'order_id' => $order->id,
                'is_guest' => $isGuest,
                'amount' => $order->amount,
                'products_count' => count($contents),
                'content_ids' => $contentIds,
                'product_names' => array_slice($contentNames, 0, 3),
            ]);

        } catch (Exception $e) {
            Log::error('Facebook Pixel Lead error: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * ✅ Process payment based on method
     */
    private function processPayment($order)
    {
        try {
            switch ($this->payment_id) {
                case '3':
                    // BOG Payment
                    return $this->redirect((new BOGPayment)->createPaymentOrder($order));
                case '4':
                    // Installment
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
                    // Part installment
                    if ($order->amount < 100) {
                        $this->dispatch('ui:error', message: 'ნაწილ-ნაწილ თანხა უნდა აღემადებოს 100 ლარს!');
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
                        $products = [];
                        foreach ($order->items as $product) {
                            $products[] = [
                                'name' => $product->product->translation('ka')->title,
                                'price' => $product->price + ($product->price * 0.05),
                                'quantity' => $product->quantity,
                            ];
                        }
                        $tbcInstallment->addProducts($products);
                        $response = $tbcInstallment->applyInstallmentApplication($order->id, $order->amount + ($order->amount * 0.05));
                        if ($response['status_code'] === 200) {
                            $redirectUri = $tbcInstallment->getRedirectUri();
                            return redirect($redirectUri);
                        }
                    }
                    break;
                case '9':
                    if ($order->amount < 150) {
                        $this->dispatch('ui:error', message: 'კრედო განვადების თანხა უნდა აღემატებოდეს 150 ლარს!');
                    } else {
                        return $this->redirect(route('credo-create-order', ['order_id' => $order['id']]));
                    }
                    break;
                case '2':
                    $this->dispatch('ui:error', message: 'შეკვეთა მიღებულია!');
                default:
                    return $this->redirect('/order/success');
            }
        } catch (Exception $e) {
            Log::error('Payment processing error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'გადახდის დამუშავება ვერ მოხერხდა');
        }
    }

    public function render()
    {
        return view('livewire.web.cart.checkout', [
            'payment_list' => Payment::where('active', 1)->orderBy('sortable', 'ASC')->get(),
        ])->layout('livewire.web.layout');
    }
}