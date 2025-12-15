<?php

namespace App\Livewire\Web\Components;

use Darryldecode\Cart\Facades\CartFacade as Cart;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCounter extends Component
{
    public $cartCount = 0;

    public function mount()
    {
        $this->updateCount();
    }

    #[On('cartUpdated')]
    public function updateCount()
    {
        $this->cartCount = Cart::getTotalQuantity();
    }

    public function render()
    {
        return view('livewire.web.components.cart-counter');
    }
}