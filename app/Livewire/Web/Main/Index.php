<?php

namespace App\Livewire\Web\Main;

use App\Models\Slider;
use Livewire\Component;
use App\Models\ProductCategory;
use App\Models\Promotion;
use Livewire\Attributes\Computed;

class Index extends Component
{
    #[Computed]
    public function productCategories()
    {
        return ProductCategory::with(['children', 'translations'])
            ->where('show', 1)
            ->where('active', 1)
            ->get();
    }

    #[Computed]
    public function promotions()
    {
        return Promotion::with(['translations', 'products.product.translations', 'products.product.price'])
            ->where('active', 1)
            ->orderBy('position')
            ->get();
    }
    #[Computed]
    public function sliders()
    {
        return Slider::where('active', 1)
            ->orderBy('sortable')
            ->get();
    }

    public function render()
    {
        return view('livewire.web.main.index', [
            'sliders'
        ])
            ->layout('livewire.web.layout');
    }
}