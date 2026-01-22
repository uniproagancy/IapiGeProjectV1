<?php

namespace App\Livewire\Web\User;

use App\Models\Cart\ShoppingCart;
use App\Models\Product\Product;
use App\Models\Order\Order;
use App\Models\User\UserVerification;
use App\Services\Sender\SmsOffice;
use App\Traits\WithCart;
use App\Traits\WithWishlist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

class Index extends Component
{
    use WithWishlist, WithCart, WithPagination;

    // ============ PROFILE PROPERTIES ============
    public $name;
    public $lastname;
    public $email;
    public $phone;

    // ============ PASSWORD PROPERTIES ============
    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    // ✅ PHONE VERIFICATION PROPERTIES
    public $showPhoneVerification = false;
    public $phone_verification_code = '';
    public $phone_verification_error = '';
    public $new_phone = '';
    public $phone_resend_available = false;
    public $phone_resend_timer = 0;

    // ============ CART & WISHLIST ============
    public $wishlistItems;
    public $cartItems;

    // ============ NOTIFICATIONS ============
    public $filter = 'all'; // all, unread, read

    // ✅ ORDER DETAILS MODAL
    public $selectedOrder = null;
    public $showOrderDetails = false;

    #[Url(keep: true)]
    public $page = '';

    protected $listeners = ['notificationRead' => '$refresh'];

    /**
     * ✅ Mount - Initialize data
     */
    public function mount()
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->lastname = $user->lastname;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->loadWishlist();
        $this->loadCart();
    }

    public function loadCart()
    {
        $this->cartItems = $this->getCartItems();
    }

    // ============================================
    // PROFILE METHODS
    // ============================================

    /**
     * ✅ Update personal information WITH phone verification
     */
    public function updatePersonalInfo()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('db_users')->ignore(Auth::id())],
            'phone' => ['required', Rule::unique('db_users')->ignore(Auth::id())],
        ], [
            'required' => 'გთხოვთ შეავსოთ ყველა აუცილებელი ველი!',
            'email.email' => 'არასწორი ელფოსტის ფორმატი',
            'email.unique' => 'ელფოსტა უკვე გამოყენებულია',
            'phone.unique' => 'ტელეფონის ნომერი უკვე გამოყენებულია',
        ]);

        if ($this->phone !== Auth::user()->phone) {
            $this->new_phone = $this->phone;
            $this->sendPhoneVerificationCode($this->phone);
            return;
        }

        Auth::user()->update([
            'name' => $this->name,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'phone' => $this->phone,
        ]);

        $this->dispatch('ui:success', message: 'პროფილი წარმატებით განახლდა!');
    }

    private function sendPhoneVerificationCode($phone)
    {
        try {
            $normalizedPhone = $this->normalizePhoneNumber($phone);
            $verificationCode = rand(1000, 9999);  // ✅ 4 digits
            UserVerification::where('user_id', Auth::id())->update([
                'status' => 0,
            ]);
            UserVerification::create([
                'user_id' => Auth::id(),
                'code' => $verificationCode,
                'status' => 1,
            ]);
            (new SmsOffice)->send($normalizedPhone, 'დადასტურების კოდი: ' . $verificationCode);
            $this->showPhoneVerification = true;
            $this->phone_resend_available = false;
            $this->phone_resend_timer = 30;
            $this->phone_verification_code = '';
            $this->phone_verification_error = '';
            $this->dispatch('openPhoneVerificationModal');
            $this->dispatchBrowserEvent('startPhoneVerificationTimer');

        } catch (\Exception $e) {
            $this->addError('phone', 'SMS გაგზავნა ვერ მოხერხდა!');
        }
    }

    public function verifyPhoneCode()
    {
        $this->validate([
            'phone_verification_code' => 'required',
        ], [
            'phone_verification_code.required' => 'დაასტურების კოდი აუცილებელია!',
            'phone_verification_code.digits' => 'კოდი უნდა იყოს 4 ციფრი!',
        ]);
        $verification = UserVerification::where('user_id', Auth::id())
            ->where('status', 1)
            ->latest()
            ->first();
        if (!$verification) {
            $this->phone_verification_error = 'დადასტურება ვერ მოიძებნა!';
            return;
        }
        if (now()->diffInMinutes($verification->created_at) > 5) {
            $this->phone_verification_error = 'დასტურების კოდი ვადაგასულია!';
            $verification->update(['status' => 0]);
            return;
        }
        if ((int)$this->phone_verification_code !== (int)$verification->code) {
            $this->phone_verification_error = 'კოდი არასწორია!';
            $this->phone_verification_code = '';
            return;
        }
        Auth::user()->update([
            'name' => $this->name,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'phone' => $this->new_phone,
        ]);
        $verification->update(['status' => 2]);
        $this->closePhoneVerification();
        $this->dispatch('ui:success', message: 'პროფილი წარმატებით განახლდა! ტელეფონი დადასტურდა!');
    }

    public function resendPhoneVerificationCode()
    {
        if ($this->phone_resend_available) {
            try {
                $normalizedPhone = $this->normalizePhoneNumber($this->new_phone);
                $verificationCode = rand(1000, 9999);  // ✅ 4 digits
                UserVerification::where('user_id', Auth::id())->update([
                    'status' => 0,
                ]);
                UserVerification::create([
                    'user_id' => Auth::id(),
                    'code' => $verificationCode,
                    'status' => 1,
                ]);
                (new SmsOffice)->send($normalizedPhone, 'დადასტურების კოდი: ' . $verificationCode);
                $this->phone_verification_code = '';
                $this->phone_verification_error = '';
                $this->phone_resend_available = false;
                $this->phone_resend_timer = 30;
                $this->dispatchBrowserEvent('startPhoneVerificationTimer');
                $this->dispatch('ui:success', message: 'კოდი ხელახლა გაიგზავნა!');

            } catch (\Exception $e) {
                $this->dispatch('ui:error', message: 'კოდის ხელახლა გაგზავნა ვერ მოხერხდა!');
            }
        }
    }

    public function closePhoneVerification()
    {
        $this->showPhoneVerification = false;
        $this->phone_verification_code = '';
        $this->phone_verification_error = '';
        $this->dispatch('closePhoneVerificationModal');
    }

    #[On('enablePhoneResend')]
    public function enablePhoneResend()
    {
        $this->phone_resend_available = true;
        $this->phone_resend_timer = 0;
        $this->dispatch('phoneResendEnabled');
    }

    #[On('updatePhoneResendTimer')]
    public function updatePhoneResendTimer($timeLeft)
    {
        $this->phone_resend_timer = $timeLeft;
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed|different:current_password',
        ], [
            'current_password.required' => 'გთხოვთ შეიყვანოთ მიმდინარე პაროლი',
            'new_password.required' => 'გთხოვთ შეიყვანოთ ახალი პაროლი',
            'new_password.min' => 'პაროლი უნდა იყოს მინიმუმ 8 სიმბოლო',
            'new_password.confirmed' => 'პაროლები არ ემთხვევა',
            'new_password.different' => 'ახალი პაროლი უნდა განსხვავდებოდეს ძველისგან',
        ]);

        if (!Hash::check($this->current_password, Auth::user()->password)) {
            $this->addError('current_password', 'მიმდინარე პაროლი არასწორია');
            return;
        }
        Auth::user()->update([
            'password' => Hash::make($this->new_password),
        ]);
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->dispatch('ui:success', message: 'პაროლი წარმატებით შეიცვალა!');
    }

    #[Computed]
    public function orders()
    {
        return Auth::user()
            ->orders()
            ->with(['items.product.translations', 'items.product.price'])->get();
    }

    public function viewOrderDetails($orderId)
    {
        $this->selectedOrder = Order::with([
            'items.product.translations',
            'items.product.price',
            'items.product.images'
        ])->find($orderId);

        if (!$this->selectedOrder) {
            $this->dispatch('ui:error', message: 'შეკვეთა ნაპოვნი არ არის!');
            return;
        }

        // Verify order belongs to current user
        if ($this->selectedOrder->user_id !== Auth::id()) {
            $this->dispatch('ui:error', message: 'თქვენ არ გაქვთ ამ შეკვეთის ნახვის უფლება!');
            return;
        }

        $this->showOrderDetails = true;
    }

    /**
     * ✅ Close order details modal
     */
    public function closeOrderDetails()
    {
        $this->showOrderDetails = false;
        $this->selectedOrder = null;
    }

    // ============================================
    // WISHLIST METHODS
    // ============================================

    /**
     * ✅ Load wishlist items
     */
    #[On('wishlistUpdated')]
    public function loadWishlist()
    {
        if (!auth()->check()) {
            return redirect()->route('web.user.sign_in');
        }

        $this->wishlistItems = auth()->user()
            ->wishlistProducts()
            ->with(['translations', 'price'])
            ->get();
    }

    /**
     * ✅ Remove item from wishlist
     */
    public function removeCartItem($itemId)
    {
        $this->removeFromCart($itemId);
        $this->loadCart();
    }

    public function removeItem($productId)
    {
        $this->removeFromWishlist($productId);
        $this->loadWishlist();
    }

    /**
     * ✅ Move wishlist item to cart
     */
    public function moveToCart($productId)
    {
        $this->addToCart($productId, 1);
        $this->removeFromWishlist($productId);
        $this->loadWishlist();
        $this->loadCart();
    }

    /**
     * ✅ Clear all wishlist items
     */
    public function clearWishlist()
    {
        if (!auth()->check()) return;

        auth()->user()->wishlists()->delete();
        $this->dispatch('wishlistUpdated');
        $this->dispatch('ui:success', message: 'სურვილების სია გაიწმინდა!');
        $this->loadWishlist();
    }

    // ============================================
    // CART METHODS
    // ============================================

    /**
     * ✅ Increment cart item quantity
     */
    public function incrementQuantity($itemId)
    {
        try {
            $item = $this->cartItems->get($itemId);
            if (!$item) {
                $this->dispatch('ui:error', message: 'პროდუქტი კალათაში ნაპოვნი არ არის!');
                return;
            }
            $product = Product::findOrFail($item->id);
            $availableQuantity = $product->quantity ?? 0;
            $newQuantity = $item->quantity + 1;
            if ($newQuantity > $availableQuantity) {
                $this->dispatch('ui:error', message: "მხოლოდ {$availableQuantity} ცალი არის ხელმისაწვდომი!");
                return;
            }
            $this->updateCartQuantity($itemId, $newQuantity);
            $this->loadCart();
            $this->dispatch('ui:success', message: 'რაოდენობა გაზარდა!');
        } catch (\Exception $e) {
            $this->dispatch('ui:error', message: 'შეცდომა რაოდენობის გაზრდისას!');
        }
    }

    public function decrementQuantity($itemId)
    {
        try {
            $item = $this->cartItems->get($itemId);
            if (!$item) {
                $this->dispatch('ui:error', message: 'პროდუქტი კალათაში ნაპოვნი არ არის!');
                return;
            }
            $newQuantity = $item->quantity - 1;
            if ($newQuantity < 1) {
                $this->removeFromCart($itemId);
                return;
            }
            $this->updateCartQuantity($itemId, $newQuantity);
            $this->loadCart();
            $this->dispatch('ui:success', message: 'რაოდენობა შემცირდა!');
        } catch (\Exception $e) {
            $this->dispatch('ui:error', message: 'შეცდომა რაოდენობის შემცირებისას!');
        }
    }

    public function clearCart()
    {
        $this->cartItems->items()->delete();
        $this->loadCart();
        $this->dispatch('ui:success', message: 'კალათა გაიწმინდა!');
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function markAsRead($notificationId)
    {
        try {
            $notification = Auth::user()->notifications()->find($notificationId);

            if ($notification) {
                $notification->markAsRead();
                $this->dispatch('notificationRead');
                $this->dispatch('ui:success', message: 'შეტყობინება წაკითხულად მონიშნულია!');
            }
        } catch (\Exception $e) {
            $this->dispatch('ui:error', message: 'შეცდომა!');
        }
    }

    /**
     * ✅ Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            Auth::user()->unreadNotifications->markAsRead();
            $this->dispatch('notificationRead');
            $this->dispatch('ui:success', message: 'ყველა შეტყობინება წაკითხულად მონიშნულია!');
        } catch (\Exception $e) {
            $this->dispatch('ui:error', message: 'შეცდომა!');
        }
    }

    /**
     * ✅ Delete single notification
     */
    public function deleteNotification($notificationId)
    {
        try {
            Auth::user()->notifications()->find($notificationId)?->delete();
            $this->dispatch('ui:success', message: 'შეტყობინება წაიშალა!');
        } catch (\Exception $e) {
            $this->dispatch('ui:error', message: 'შეცდომა წაშლის დროს!');
        }
    }

    /**
     * ✅ Delete all notifications
     */
    public function deleteAll()
    {
        try {
            Auth::user()->notifications()->delete();
            $this->dispatch('ui:success', message: 'ყველა შეტყობინება წაიშალა!');
        } catch (\Exception $e) {
            $this->dispatch('ui:error', message: 'შეცდომა!');
        }
    }

    /**
     * ✅ Get filtered notifications
     */
    #[Computed]
    public function notifications()
    {
        $query = Auth::user()->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        return $query->paginate(10);
    }

    /**
     * ✅ Get unread notifications count
     */
    #[Computed]
    public function unreadCount()
    {
        return Auth::user()->unreadNotifications()->count();
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * ✅ Normalize phone number
     */
    private function normalizePhoneNumber($phone)
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) === 9) {
            return $phone;
        } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '995') {
            return substr($phone, 3, 9);
        } elseif (strlen($phone) === 13 && substr($phone, 0, 4) === '+995') {
            return substr($phone, 4, 9);
        }
        return $phone;
    }

    // ============================================
    // RENDER
    // ============================================

    public function render()
    {
        return view('livewire.web.user.index', [
            'orders' => $this->orders,
            'notifications' => $this->notifications,
            'unreadCount' => $this->unreadCount,
            'cartItems' => $this->getCartItems(),
            'selectedOrder' => $this->selectedOrder,
            'showOrderDetails' => $this->showOrderDetails,
            'showPhoneVerification' => $this->showPhoneVerification,
            'new_phone' => $this->new_phone,
        ])->layout('livewire.web.layout');
    }
}