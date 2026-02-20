<?php

namespace App\Livewire\Web\Promotions;

use App\Models\PromotionPages\Product;
use App\Models\PromotionPages\Page;
use Livewire\Component;

class Index extends Component
{

    protected $promotionSlug;
    public function mount($promotionSlug) {
        $promotionPage = Page::where('slug', $promotionSlug)->firstOrFail();
        $promotionProducts = Product::where('promotion_id', $promotionPage->id)->get();
    }
    public function render()
    {
        return view('livewire.web.promotions.index')->layout('livewire.web.layout');
    }
}
