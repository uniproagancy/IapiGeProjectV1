<?php

namespace App\Livewire\Web\Main;

use App\Models\Product\ProductBrand;
use App\Services\Facebook\FacebookPixelService;
use Livewire\Component;
use Livewire\Attributes\Computed;

use App\Models\Content\Slider;
use App\Models\Product\ProductCategory;
use App\Models\Product\Promotion;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class Index extends Component
{
    public string $eventId = '';

    public function mount()
    {
        $this->eventId = view()->shared('fb_event_id', 'pv_' . time() . '_' . Str::random(6));
        app(FacebookPixelService::class)->trackPageViewWithTest('TEST61083',[], $this->eventId);
    }

    #[Computed]
    public function productCategories()
    {
        return cache()->remember('product_categories_active', 3600, fn () =>
        ProductCategory::with(['children', 'translations'])
            ->where('show', 1)
            ->where('active', 1)
            ->get()
        );
    }

    #[Computed]
    public function promotions()
    {
        return cache()->remember('promotions_active', 1800, function () {
            return Promotion::with([
                'translations',
                'products' => fn ($q) => $q
                    ->whereHas('product', fn ($q) => $q
                        ->where('active', 1)
                        ->where('show', 1)
                    )
                    ->limit(20), // ✅ მაქსიმუმ 20 პროდუქტი თითო პრომოუშენზე
                'products.product' => fn ($q) => $q
                    ->select('id', 'main_image', 'category_id', 'brand_id')
                    ->where('active', 1)
                    ->where('show', 1),
                'products.product.translations' => fn ($q) => $q
                    ->select('id', 'product_id', 'title', 'slug', 'locale')
                    ->where('locale', app()->getLocale()),
                'products.product.price' => fn ($q) => $q
                    ->select('id', 'product_id', 'regular_price', 'discount_price'),
            ])
                ->where('active', 1)
                ->orderBy('position')
                ->get();
        });
    }

    #[Computed]
    public function sliders()
    {
        return cache()->remember('sliders_active', 3600, fn () =>
        Slider::where('active', 1)
            ->orderBy('sortable')
            ->get()
        );
    }

    #[Computed]
    public function brands()
    {
        return cache()->remember('brands_active', 3600, fn () =>
        ProductBrand::where('active', 1)
            ->where('show', 1)
            ->orderBy('sortable')
            ->get()
        );
    }

    public function render()
    {
        return view('livewire.web.main.index')->layout('livewire.web.layout');
    }
}