<?php

namespace App\Livewire\Web\Product;

use App\Models\Payments\Payment;
use App\Models\Product\Product;
use App\Models\User\User;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderDelivery;
use Giorgijorji\LaravelTbcInstallment\LaravelTbcInstallment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

use Illuminate\Support\Facades\Hash;

use Log;

class CheckoutModal extends Component
{
    public $selectedProduct = null;
    public $payment_list = [];

    public $name = '';
    public $lastname = '';
    public $email = '';
    public $phone = '';
    public $address = '';
    public $comment = '';
    public $payment_id = null;
    public $subtotal = 0;

    public $total = 0;
    public $productId = 0;

    public function mount()
    {
        if (Auth::check()) {
            $this->name = Auth::user()->name;
            $this->lastname = Auth::user()->lastname;
            $this->email = Auth::user()->email;
            $this->phone = Auth::user()->phone;
        }
        $this->loadPaymentMethods();
    }

    #[On('openCheckoutModal')]
    public function openCheckoutModal($productId)
    {
        $this->productId = $productId;
        dd($this->productId);
    }

    private function loadPaymentMethods()
    {
        $this->payment_list = Payment::where('active', 1)->orderBy('sortable', 'ASC')->get();
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

    public function placeOrder()
    {
        try {
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
            if (Auth::check()) {
                $order_user_id = Auth::user()->id;
            } else {
                $user = User::create([
                    'name' => $this->name,
                    'lastname' => $this->lastname,
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'password' => Hash::make('password'),
                ]);
                $order_user_id = $user->id;
            }
            $product = Product::find(9);
            $order = Order::create([
                'user_id' => $order_user_id,
                'payment_id' => $this->payment_id,
                'comment' => $this->comment,
                'created_by' => $order_user_id,
                'delivery_amount' => 0,
                'amount' => $this->subtotal,
            ]);
            OrderItem::create([
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => $product->price->discount_price ?? $product->price->regular_price,
                'order_id' => $order->id,
            ]);
            OrderDelivery::create([
                'order_id' => $order->id,
                'address' => $this->address,
            ]);
            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'amount' => $order->amount,
            ]);
            $this->processPayment($order);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // ✅ Get first error field
            $errorField = array_key_first($e->errors());
            Log::warning('Validation error in checkout', [
                'error_field' => $errorField,
                'errors' => $e->errors(),
            ]);
            $this->dispatch('scrollToError', field: $errorField);
            $this->dispatch('ui:error', message: 'გთხოვთ შეამოწმეთ ფორმა');
        } catch (Exception $e) {
            Log::error('Order creation error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეკვეთის შექმნა ვერ მოხერხდა. სცადეთ ისევ.');
        }
    }

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
        return view('livewire.web.product.checkout-modal', [
            'payment_list' => $this->payment_list,
            'phone' => $this->phone,
        ]);
    }
}