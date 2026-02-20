<?php

namespace App\Livewire\Web\Promotions;

use App\Models\PromotionPages\Product;
use App\Models\PromotionPages\Page;
use Livewire\Component;

class Index extends Component
{

    public $sliderImg;

    protected $promotionSlug;
    protected $promotionTitle;
    public function mount($promotionSlug) {
        $promotionPage = Page::where('slug', $promotionSlug)->firstOrFail();
        $promotionProducts = Product::where('promotion_id', $promotionPage->id)->get();
        $this->sliderImg = $promotionPage->image;
        $this->promotionTitle = $promotionPage->title;
    }
    public function render()
    {
        return view('livewire.web.promotions.index', [
            'sliderImg' => $this->sliderImg ?? null,
            'promotionTitle' => $this->promotionTitle ?? null,
        ])->layout('livewire.web.layout');
    }
}
