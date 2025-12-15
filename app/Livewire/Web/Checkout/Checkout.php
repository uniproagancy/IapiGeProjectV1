<?php

namespace App\Livewire\Web\Checkout;

use App\Models\City;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Services\Payments\BOGPayment;
use App\Traits\WithCart;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class Checkout extends Component
{
    use WithCart;

    // Customer Info
    public $name = '';
    public $lastname = '';
    public $email = '';
    public $phone = '';
    public $verify_phone = '';

    // Shipping Address
    public $selected_address_id = null;
    public $city_id = null;
    public $address = '';
    public $comment = '';

    // Delivery
    public $shipping_cost = 0;

    // Payment
    public $payment_id = '';
    public $payment_provider_id = null;

    // Direct Purchase
    #[Url(as: 'product_id')]
    public $product_id = null;

    #[Url(as: 'quantity')]
    public $quantity = 1;

    public $orderItems = [];
    public $subtotal = 0;
    public $total = 0;
    public $userAddresses = [];

    public function mount()
    {
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

            $this->loadUserAddresses();

            $defaultAddress = $user->defaultAddress;
            if ($defaultAddress) {
                $this->selectAddress($defaultAddress->id);
            }
        }
        $this->loadOrderItems();
        $this->calculateShippingCost();
        $this->calculateTotals();
    }


    public function loadUserAddresses()
    {
        if (auth()->check()) {
            $this->userAddresses = auth()->user()->addresses()->latest()->get();
        }
    }

    public function loadOrderItems()
    {
        if ($this->product_id) {
            try {
                $product = Product::with(['translations', 'price'])
                    ->where('active', 1)
                    ->where('show', 1)
                    ->findOrFail($this->product_id);
                if ($product->in_stock !== 1) {
                    $this->dispatch('notify', message: 'პროდუქტი არ არის მარაგში', type: 'error');
                    return $this->redirect(route('web.products.index'), navigate: true);
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
            } catch (\Exception $e) {
                $this->dispatch('notify', message: 'პროდუქტი ვერ მოიძებნა', type: 'error');
                return redirect()->route('web.main.index');
            }
        } else {
            $this->loadCartFromDatabase();
            $cartItems = Cart::getContent();
            if ($cartItems->isEmpty()) {
                $this->dispatch('notify', message: 'თქვენი კალათა ცარიელია', type: 'warning');
                return $this->redirect(route('web.cart.index'), navigate: true);
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
    }

    public function updatedCityId()
    {
        $this->calculateShippingCost();
        $this->calculateTotals();
    }

    public function calculateShippingCost()
    {
        if(!empty($this->city_id)) {
            $city_data = City::where('id', $this->city_id)->first();
            $this->shipping_cost = $city_data->delivery_amount;
        }
    }

    public function calculateTotals()
    {
        $this->subtotal = $this->orderItems->sum('total');
        $this->tax = 0;
        $this->total = $this->subtotal + $this->shipping_cost + $this->tax;
    }

    public function placeOrder()
    {
        $this->validate([
            'city_id' => 'required|string|max:255|exists:db_cities,id',
            'address' => 'required|string',
            'payment_id' => 'required',
        ]);
        if(Auth::check()) {
            $city = City::find($this->city_id);
            $order = Order::create([
                'user_id' => Auth::user()->id,
                'payment_id' => $this->payment_id,
                'comment' => $this->comment,
                'created_by' => Auth::user()->id,
                'delivery_amount' => $city->delivery_amount,
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
        }
        Cart::clear();
        switch ($this->payment_id) {
            case '3':
                return $this->redirect((new \App\Services\Payments\BOGPayment)->createPaymentOrder($order));
            break;
            case '4':
                if($order->amount < 100) {
                    $this->dispatch('ui:error', message: 'განვადების თანხა უნდა აღემატებოდეს 100 ლარს');
                } else {
                    $this->dispatch('bog:installment', amount: $order->amount + ($order->amount * 0.05), url: route('bog.installment', $order->id));
                }
            break;
            case '5':
                if($order->amount < 100) {
                    $this->dispatch('ui:error', message: 'ნაწილ-ნაწილ თანხა უნდა აღემადებოს 100 ლარს');
                } else {
                    $this->dispatch('bog:installment-part', amount: $order->amount + $order->delivery_amount, url: route('bog.part-installment', $order->id));
                }
            break;
            default:
                redirect()->route('web.main.index');
            break;
        }
    }

    public function render()
    {
        return view('livewire.web.cart.checkout', [
            'cities_list' => City::where('active', 1)->get(),
            'payment_list' => Payment::where('active', 1)->orderBy('sortable', 'ASC')->get(),
        ])
            ->layout('livewire.web.layout');
    }
}