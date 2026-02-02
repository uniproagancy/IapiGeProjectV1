<?php

namespace App\Livewire\Web\Checkout;

use App\Models\Order\Order;
use App\Models\Order\OrderDelivery;
use App\Models\Order\OrderItem;
use App\Models\Payments\Payment;
use App\Models\Product\Product;
use App\Services\Payments\BOGPayment;
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
    public $userAddresses = [];
    public $selected_address_id;

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

                $this->loadUserAddresses();

                $defaultAddress = $user->defaultAddress;
                if ($defaultAddress) {
                    $this->selectAddress($defaultAddress->id);
                }
            }
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

            Log::info('Checkout form validated', [
                'email' => $this->email,
            ]);

            // ✅ Track checkout initiation (Facebook Pixel)

            // ✅ Create order
            if (Auth::check()) {
                $order = Order::create([
                    'user_id' => Auth::user()->id,
                    'payment_id' => $this->payment_id,
                    'comment' => $this->comment,
                    'created_by' => Auth::user()->id,
                    'delivery_amount' => 0,
                    'amount' => $this->subtotal,
                ]);

                // ✅ Add order items
                foreach ($this->orderItems as $orderItem) {
                    OrderItem::create([
                        'product_id' => $orderItem['id'],
                        'quantity' => $orderItem['quantity'],
                        'price' => $orderItem['price'],
                        'order_id' => $order->id,
                    ]);
                }

                // ✅ Add delivery info
                OrderDelivery::create([
                    'order_id' => $order->id,
                    'address' => $this->address,
                ]);

                Log::info('Order created successfully', [
                    'order_id' => $order->id,
                    'amount' => $order->amount,
                ]);

                // ✅ Track purchase (Facebook Pixel)


                // ✅ Clear cart
                Cart::clear();

                // ✅ Process payment
                $this->processPayment($order);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            // ✅ Get first error field
            $errorField = array_key_first($e->errors());

            Log::warning('Validation error in checkout', [
                'error_field' => $errorField,
                'errors' => $e->errors(),
            ]);

            // ✅ Dispatch event to scroll to error field
            $this->dispatch('scrollToError', field: $errorField);

            // ✅ Show error message
            $this->dispatch('ui:error', message: 'გთხოვთ შეამოწმეთ ფორმა');

        } catch (Exception $e) {
            Log::error('Order creation error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეკვეთის შექმნა ვერ მოხერხდა. სცადეთ ისევ.');
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
                  if($order->amount < 150) {
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
                    if($response['status_code'] === 200) {
                        $redirectUri = $tbcInstallment->getRedirectUri();
                        return redirect($redirectUri);
                    }
                  }
                break;
                case '9':
                      if($order->amount < 150) {
                        $this->dispatch('ui:error', message: 'კრედო განვადების თანხა უნდა აღემატებოდეს 150 ლარს!');
                      } else {
                        return $this->redirect(route('credo-create-order', ['order_id' => $order['id']]));
                      }
                break;
                case '2':
                    // Invoice
                    return $this->redirect('/checkout/success');
                default:
                    return $this->redirect('/checkout/success');
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