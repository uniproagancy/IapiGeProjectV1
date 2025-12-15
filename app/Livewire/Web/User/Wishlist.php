<?php

namespace App\Livewire\Web\User;

use Livewire\Attributes\On;
use Livewire\Component;

class Wishlist extends Component
{
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

    public function removeItem($productId)
    {
        $this->removeFromWishlist($productId);
        $this->loadWishlist();
    }

    public function moveToCart($productId)
    {
        $this->addToCart($productId, 1);
        $this->removeFromWishlist($productId);
        $this->loadWishlist();
        $this->loadCart();
    }

    public function clearWishlist()
    {
        if (!auth()->check()) {
            return;
        }
        auth()->user()->wishlists()->delete();
        $this->dispatch('wishlistUpdated');
        $this->dispatch('ui:success', message: 'სურვილების სია გაიწმინდა');
        $this->loadWishlist();
    }
    public function render()
    {
        return view('livewire.web.user.wishlist');
    }
}
