<?php

namespace App\Livewire\Web\Components;

use Livewire\Component;
use App\Traits\WithWishlist;
use Livewire\Attributes\On;

class WishlistCounter extends Component
{
    use WithWishlist;

    public $wishlistCount = 0;

    public function mount()
    {
        $this->updateCount();
    }

    #[On('wishlistUpdated')]
    public function updateCount()
    {
        $this->wishlistCount = $this->getWishlistCount();
    }

    public function render()
    {
        return view('livewire.web.components.wishlist-counter');
    }
}