<?php

namespace App\Livewire\Web\Product;

use App\Models\Payments\Payment;
use Livewire\Component;
use App\Models\Product\Product;
use App\Traits\WithCart;
use App\Services\Facebook\FacebookPixelService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class View extends Component
{
    use WithCart;

    public $product;
    public $quantity = 1;
    public string $eventId;

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
        })->firstOrFail();

        $this->loadCartFromDatabase();

        $this->eventId = 'vc_' . time() . '_' . Str::random(6);

        $pxl_price = ($this->product->price?->discount_price && $this->product->price->discount_price > 0)
            ? $this->product->price->discount_price
            : $this->product->price->regular_price;

        app(FacebookPixelService::class)->trackViewContentWithTest('TEST61083',[
            'id'               => $this->product->id,
            'name'             => $this->product->translation('ka')->title,
            'price'            => $pxl_price,
            'event_source_url' => url()->current(),
        ], [], $this->eventId);
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

    public function getInstallments()
    {
        return Payment::where('type', 3)->where('active', 1)->get();
    }

    public function render()
    {
        return view('livewire.web.product.view', [
            'similarProducts' => $this->getSimilarProducts(),
            'installments' => $this->getInstallments(),
            'event_id' => $this->eventId,
        ])->layout('livewire.web.layout');
    }
}