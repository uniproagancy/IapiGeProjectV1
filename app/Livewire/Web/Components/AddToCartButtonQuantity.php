<?php

namespace App\Livewire\Web\Components;

use App\Traits\WithCart;
use Illuminate\Support\Str;
use Livewire\Component;

class AddToCartButtonQuantity extends Component
{
    use WithCart;

    public $productId;
    public $quantity = 1;
    public $cartEventId;

    public function mount($productId, $quantity = 1)
    {
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->pageUrl    = url()->current();
    }

    public function addProduct()
    {
        $this->cartEventId = 'ac_' . time() . '_' . Str::random(6);
        $this->addToCart($this->productId, $this->quantity, $this->cartEventId, $this->pageUrl); // ✅ ფრჩხილების გარეშე
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
        return view('livewire.web.components.add-to-cart-button-quantity');
    }
}