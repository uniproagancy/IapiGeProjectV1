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
        $validated = $this->validate([
            'name' => 'required|string|min:2|max:50',
            'lastname' => 'required|string|min:2|max:50',
            'email' => 'required|email',
            'phone' => 'required|regex:/^(\+?995)?5[0-9]{8}$/',
            'address' => 'required|string|min:5|max:200',
            'payment_id' => 'required|exists:payment_methods,id',
            'selectedProduct' => 'required',
        ]);

        // ✅ Send OTP to phone
        $this->sendOTP($this->phone);

        // ✅ Move to OTP step
        $this->step = 'otp';
    }

    // ============================================
    // OTP Verification Logic
    // ============================================

    private function sendOTP($phone)
    {
        try {
            // ✅ Normalize phone number
            $normalizedPhone = $this->normalizePhoneNumber($phone);

            // ✅ Generate OTP code
            $otpCode = rand(100000, 999999);

            // ✅ Store in session/cache (expires in 5 minutes)
            session([
                'otp_code' => $otpCode,
                'otp_phone' => $normalizedPhone,
                'otp_created_at' => now(),
                'otp_attempts' => 0,
            ]);

            // ✅ Send SMS via smsoffice.ge or similar service
            // SMS::send($normalizedPhone, "თქვენი დასტური კოდი: {$otpCode}");

            // For demo (remove in production)
            \Log::info("OTP Code: {$otpCode} for phone: {$normalizedPhone}");

            // ✅ Enable resend after 30 seconds
            $this->otp_resend_available = false;
            $this->otp_resend_timer = 30;

            // ✅ Start countdown timer
            $this->dispatchBrowserEvent('startOTPTimer');

        } catch (\Exception $e) {
            $this->addError('phone', 'SMS გაგზავნა ვერ მოხერხდა. სცადეთ ისევ.');
            \Log::error('OTP Send Error: ' . $e->getMessage());
        }
    }

    public function verifyOTP()
    {
        // ✅ Validate OTP input
        $this->validate([
            'otp_code' => 'required|numeric|digits:6',
        ]);

        // ✅ Get stored OTP from session
        $storedOTP = session('otp_code');
        $otpCreatedAt = session('otp_created_at');
        $otpAttempts = session('otp_attempts', 0);

        // ✅ Check if OTP expired (5 minutes)
        if (now()->diffInMinutes($otpCreatedAt) > 5) {
            $this->otp_error = 'დასტური კოდი ვადაგასულია. სცადეთ ხელახლა.';
            session()->forget(['otp_code', 'otp_phone', 'otp_created_at']);
            return;
        }

        // ✅ Check attempts (max 3)
        if ($otpAttempts >= 3) {
            $this->otp_error = 'ძალიან ბევრი მცდელობა. სცადეთ ისევ 5 წუთში.';
            return;
        }

        // ✅ Verify OTP code
        if ((int)$this->otp_code !== (int)$storedOTP) {
            $otpAttempts++;
            session(['otp_attempts' => $otpAttempts]);
            $this->otp_error = "დასტური კოდი არასწორია. დარჩა " . (3 - $otpAttempts) . " მცდელობა.";
            $this->otp_code = '';
            return;
        }

        // ✅ OTP Verified! Create order
        $this->createOrder();

        // ✅ Clear session
        session()->forget(['otp_code', 'otp_phone', 'otp_created_at', 'otp_attempts']);
    }

    public function resendOTP()
    {
        if ($this->otp_resend_available) {
            $this->sendOTP($this->phone);
            $this->otp_code = '';
            $this->otp_error = '';
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