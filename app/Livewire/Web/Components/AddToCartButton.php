<?php

namespace App\Livewire\Web\Components;

use App\Traits\WithCart;
use Illuminate\Support\Str;
use Livewire\Component;

class AddToCartButton extends Component
{
    use WithCart;

    public $productId;
    public $quantity = 1;
    public $productPrice;
    public $productTitle;

    public $cartEventId;

    public function mount($productId, $quantity = 1)
    {
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->cartEventId = 'ac_' . time() . '_' . Str::random(6);
    }

    public function addProduct()
    {
        $this->addToCart($this->productId, $this->quantity, $this->cartEventId);
        $this->quantity = 1;
    }

    public function incrementQuantity()
    {
        $this->quantity++;
    }

    public function decrementQuantity()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function render()
    {
        return view('livewire.web.components.add-to-cart-button');
    }
}