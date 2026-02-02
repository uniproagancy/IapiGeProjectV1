<?php

namespace App\Livewire\Web\Product;

use App\Models\Payments\Payment;
use App\Models\Product\Product;
use App\Models\User\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

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

    private function loadPaymentMethods()
    {
        $this->payment_list = Payment::where('active', 1)->orderBy('sortable', 'ASC')->get();
    }

    public function submitCheckout()
    {
        //
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
                $order = Order::create([
                    'user_id' => Auth::user()->id,
                    'payment_id' => $this->payment_id,
                    'comment' => $this->comment,
                    'created_by' => Auth::user()->id,
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
                Log::info('Order created successfully', [
                    'order_id' => $order->id,
                    'amount' => $order->amount,
                ]);
                Cart::clear();
                $this->processPayment($order);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
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

    private function resetCheckout()
    {
        $this->name = '';
        $this->lastname = '';
        $this->email = '';
        $this->phone = '';
        $this->address = '';
        $this->comment = '';
        $this->payment_id = null;
    }

    public function render()
    {
        return view('livewire.web.product.checkout-modal', [
            'payment_list' => $this->payment_list,
            'phone' => $this->phone,
        ]);
    }
}