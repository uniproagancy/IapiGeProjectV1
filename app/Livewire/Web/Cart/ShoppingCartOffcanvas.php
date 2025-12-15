<?php

namespace App\Livewire\Web\Cart;

use App\Models\Product\Product;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Traits\WithCart;

class ShoppingCartOffcanvas extends Component
{
    use WithCart;

    public $cartItems;
    public $cartTotal;
    public $cartSubTotal;

    public function mount()
    {
        $this->loadCartFromDatabase();
        $this->loadCart();
    }

    #[On('cartUpdated')]
    public function loadCart()
    {
        $this->cartItems = $this->getCartItems();
        $this->cartTotal = $this->getCartTotal();
        $this->cartSubTotal = $this->getCartSubTotal();
    }

    public function incrementQuantity($itemId)
    {
        try {
            $item = $this->cartItems->get($itemId);
            if (!$item) {
                $this->dispatch('ui:error', message: 'პროდუქტი კალათაში ნაპოვნი არ არის!', type: 'error');
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
            $this->dispatch('ui:success', message: 'რაოდენობა გაზარდა!', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('ui:error', message: 'შეცდომა რაოდენობის გაზრდისას!', type: 'error');
        }
    }

    public function decrementQuantity($itemId)
    {
        try {
            $item = $this->cartItems->get($itemId);
            if (!$item) {
                $this->dispatch('ui:error', message: 'პროდუქტი კალათაში ნაპოვნი არ არის!', type: 'error');
                return;
            }
            $newQuantity = $item->quantity - 1;
            if ($newQuantity < 1) {
                $this->removeFromCart($itemId);
                return;
            }
            $this->updateCartQuantity($itemId, $newQuantity);
            $this->loadCart();
            $this->dispatch('ui:success', message: 'რაოდენობა შემცირდა!', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('ui:error', message: 'შეცდომა რაოდენობის შემცირებისას!', type: 'error');
        }
    }

    public function removeItem($itemId)
    {
        $this->removeFromCart($itemId);
        $this->loadCart();
    }

    public function render()
    {
        return view('livewire.web.cart.shopping-cart-offcanvas');
    }
}