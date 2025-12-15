<?php

namespace App\Livewire\Web\Product;

use Livewire\Component;
use App\Models\Product\Product;
use App\Traits\WithCart;

class View extends Component
{
    use WithCart;

    public $product;
    public $quantity = 1;

    public function mount($slug)
    {
        $this->product = Product::with([
            'translations',
            'price',
            'category.parent.translations',
            'category.translations',
            'images',
            'brand',
            'shortSpecifications'
        ])->whereHas('translations', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })
        ->firstOrFail();
        $this->loadCartFromDatabase();
    }

    public function getSimilarProducts()
    {
        return Product::with(['translations', 'price'])
            ->where('category_id', $this->product->category_id)
            ->where('id', '!=', $this->product->id)
            ->where('active', 1)
            ->where('show', 1)
            ->take(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.web.product.view', [
            'similarProducts' => $this->getSimilarProducts()
        ])->layout('livewire.web.layout');
    }
}