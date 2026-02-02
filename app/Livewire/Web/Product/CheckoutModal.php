<?php

namespace App\Livewire\Web\Product;

use App\Models\Payments\Payment;
use App\Models\Product\Product;
use App\Models\User\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CheckoutModal extends Component
{
    // ============================================
    // Properties
    // ============================================

    public $selectedProduct = null;
    public $payment_list = [];

    // ✅ Step management
    public $step = 'checkout'; // 'checkout' | 'otp'

    // ✅ Form Fields
    public $name = '';
    public $lastname = '';
    public $email = '';
    public $phone = '';
    public $address = '';
    public $comment = '';
    public $payment_id = null;

    // ✅ OTP Fields
    public $otp_code = '';
    public $otp_error = '';
    public $otp_resend_available = false;
    public $otp_resend_timer = 0;
    public $otp_session_id = null;

    // ============================================
    // Lifecycle Hooks
    // ============================================

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

    // ============================================
    // Checkout Form Submit
    // ============================================

    public function submitCheckout()
    {
        //
        try {
            // ✅ Validation rules
            $validated = $this->validate([
                'city_id' => 'string|max:255|exists:db_cities,id',
                'address' => 'required|string|max:255',
                'payment_id' => 'required',
            ], [
                'city_id.required' => 'ქალაქი აუცილებელია',
                'city_id.exists' => 'არჩეული ქალაქი ვერ მოიძებნა',
                'address.required' => 'მისამართი აუცილებელია',
                'address.min' => 'მისამართი უნდა იყოს მინიმუმ 5 სიმბოლოსი',
                'payment_id.required' => 'გადახდის მეთოდი აუცილებელია',
            ]);

            Log::info('Checkout form validated', [
                'email' => $this->email,
                'city_id' => $this->city_id,
            ]);

            // ✅ Track checkout initiation (Facebook Pixel)

            // ✅ Create order
            if (Auth::check()) {
                $city = City::find($this->city_id);

                $order = Order::create([
                    'user_id' => Auth::user()->id,
                    'payment_id' => $this->payment_id,
                    'comment' => $this->comment,
                    'created_by' => Auth::user()->id,
                    'delivery_amount' => $city->delivery_amount,
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
                    'city_id' => $this->city_id,
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

    public function backToCheckout()
    {
        $this->step = 'checkout';
        $this->otp_code = '';
        $this->otp_error = '';
        session()->forget(['otp_code', 'otp_phone', 'otp_created_at', 'otp_attempts']);
    }

    // ============================================
    // Order Creation
    // ============================================

    private function createOrder()
    {
        try {
            // ✅ Create order in database
            // $order = Order::create([
            //     'name' => $this->name,
            //     'lastname' => $this->lastname,
            //     'email' => $this->email,
            //     'phone' => $this->phone,
            //     'address' => $this->address,
            //     'comment' => $this->comment,
            //     'payment_method_id' => $this->payment_id,
            //     'product_id' => $this->selectedProduct->id,
            // ]);

            // ✅ Process payment
            // $this->processPayment($order);

            // ✅ Close modal and show success
            $this->dispatch('checkoutSuccess', orderId: 'ORDER-12345');

            // ✅ Reset form
            $this->resetCheckout();

        } catch (\Exception $e) {
            $this->addError('general', 'შეკვეთის შექმნა ვერ მოხერხდა. სცადეთ ისევ.');
            \Log::error('Order Creation Error: ' . $e->getMessage());
        }
    }

    // ============================================
    // Helper Methods
    // ============================================

    private function normalizePhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/\D/', '', $phone);

        // Handle different formats
        if (strlen($phone) === 9) {
            return '995' . $phone;
        } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '995') {
            return $phone;
        } elseif (strlen($phone) === 13 && substr($phone, 0, 4) === '+995') {
            return substr($phone, 1);
        }

        return $phone;
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
        $this->otp_code = '';
        $this->otp_error = '';
        $this->step = 'checkout';
        $this->selectedProduct = null;
    }

    // ============================================
    // Rendering
    // ============================================

    public function render()
    {
        return view('livewire.web.product.checkout-modal', [
            'payment_list' => $this->payment_list,
            'step' => $this->step,
            'phone' => $this->phone,
            'otp_error' => $this->otp_error,
            'otp_resend_available' => $this->otp_resend_available,
            'otp_resend_timer' => $this->otp_resend_timer,
        ]);
    }
}