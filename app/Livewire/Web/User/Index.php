<?php

namespace App\Livewire\Web\User;

use App\Models\Cart\ShoppingCart;
use App\Traits\WithCart;
use App\Traits\WithWishlist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\On;

class Index extends Component
{
    use WithWishlist, WithCart;

    public $name;
    public $lastname;
    public $email;
    public $phone;
    public $wishlistItems;
    public $cart;
    public $promoCode = '';
    public $promoMessage = '';
    public $promoSuccess = false;

    public $current_password;
    public $new_password;
    public $new_password_confirmation;

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
        // auth()->user()->cart ან session-დან
        $this->cart = auth()->user()->cart ?? ShoppingCart::firstOrCreate(
            ['user_id' => auth()->id()]
        );
        $this->cart->load('product');
    }

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
        ]);

        $user = Auth::user();
        $user->update([
            'name' => $this->name,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'phone' => $this->phone,
        ]);
        $this->dispatch('ui:success', message: 'პროფილი წარმატებით განახლდა!', type: 'success');
    }

    public function updatePassword()
    {
        $user = Auth::user();
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed|different:current_password',
            'new_password_confirmation' => 'required',
        ], [
            'required' => 'გთხოვთ შეავსოთ ყველა აუცილებელი ველი!',
            'new_password.min' => 'პაროლი უნდა იყოს მინიმუმ 6 სიმბოლო',
            'new_password.confirmed' => 'პაროლები არ ემთხვევა',
            'new_password.different' => 'ახალი პაროლი უნდა განსხვავდებოდეს მიმდინარე პაროლისგან',
        ]);
        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'მიმდინარე პაროლი არასწორია');
            return;
        }
        $user->update([
            'password' => Hash::make($this->new_password),
        ]);
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->dispatch('ui:success', message: 'პაროლი წარმატებით შეიცვალა!', type: 'success');
    }

    public function getOrders()
    {
        $orders = auth()->user()->orders()
            ->with(['items.product.translations', 'items.product.price'])->paginate(10);
        return $orders;
    }

    public function incrementQuantity($itemId)
    {
        $item = $this->cart->items()->find($itemId);
        if ($item) {
            $item->increment('quantity');
            $item->update(['total' => $item->quantity * $item->price]);
            $this->loadCart();
        }
    }

    public function decrementQuantity($itemId)
    {
        $item = $this->cart->items()->find($itemId);
        if ($item && $item->quantity > 1) {
            $item->decrement('quantity');
            $item->update(['total' => $item->quantity * $item->price]);
            $this->loadCart();
        }
    }

    public function removeCartItem($itemId)
    {
        $this->cart->items()->find($itemId)?->delete();
        $this->loadCart();
        $this->dispatch('ui:success', message: 'პროდუქტი წაშლილია', type: 'info');
    }

    public function clearCart()
    {
        $this->cart->items()->delete();
        $this->loadCart();
        $this->dispatch('ui:success', message: 'კალათა გაიწმინდა', type: 'info');
    }

    public function render()
    {
        return view('livewire.web.user.index', [
            'orders' => $this->getOrders()
        ])->layout('livewire.web.layout');
    }
}