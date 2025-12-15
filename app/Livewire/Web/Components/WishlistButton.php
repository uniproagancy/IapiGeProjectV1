<?php

namespace App\Livewire\Web\Components;

use Livewire\Component;
use App\Traits\WithWishlist;
use Livewire\Attributes\On;

class WishlistButton extends Component
{
    use WithWishlist;

    public $productId;
    public $isInWishlist = false;

    public function mount($productId)
    {
        $this->productId = $productId;
        $this->checkWishlist();
    }

    #[On('wishlistUpdated')]
    public function checkWishlist()
    {
        $this->isInWishlist = $this->isInWishlist($this->productId);
    }

    public function toggle()
    {
        $this->toggleWishlist($this->productId);
        $this->checkWishlist();
    }

    public function render()
    {
        return view('livewire.web.components.wishlist-button');
    }
}