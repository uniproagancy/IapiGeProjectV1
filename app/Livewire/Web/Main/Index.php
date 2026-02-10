<?php

namespace App\Livewire\Web\Main;

use App\Models\Product\ProductBrand;
use App\Services\Google\GoogleSheet;
use App\Services\Sender\SmsOffice;
use Livewire\Component;
use Livewire\Attributes\Computed;

use App\Models\Content\Slider;
use App\Models\Product\ProductCategory;
use App\Models\Product\Promotion;

use App\Services\Facebook\FacebookPixelService;
use Illuminate\Support\Str;

class Index extends Component
{
    public string $eventId;

    public function mount()
    {
        // ✅ Generate event_id once on mount (initial page load only)
        $this->eventId = 'pv_' . time() . '_' . Str::random(6);

        // ✅ Track PageView only once per page load
        app(FacebookPixelService::class)->trackPageViewWithTest('TEST68876', [
            'event_id' => $this->eventId,
        ]);
    }

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

    #[Computed]
    public function brands()
    {
        return ProductBrand::where('active', 1)
            ->where('show', 1)
            ->orderBy('sortable')
            ->get();
    }

    public function render()
    {
        return view('livewire.web.main.index', [
            'sliders' => $this->sliders,
            'brands' => $this->brands,
            'event_id' => $this->eventId, // ✅ Pass to view for browser-side tracking
        ])->layout('livewire.web.layout');
    }
}