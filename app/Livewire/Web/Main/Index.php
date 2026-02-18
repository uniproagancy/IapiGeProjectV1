<?php

namespace App\Livewire\Web\Main;

use App\Models\Product\ProductBrand;
use Livewire\Component;
use Livewire\Attributes\Computed;

use App\Models\Content\Slider;
use App\Models\Product\ProductCategory;
use App\Models\Product\Promotion;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class Index extends Component
{
    public string $eventId;
    public bool $loaded = false;

    public function mount()
    {
        $this->eventId = view()->shared('fb_event_id', 'pv_' . time() . '_' . Str::random(6));
        Log::info('🔍 Index::mount() called', [
            'event_id' => $this->eventId,
        ]);
    }

    public function loadContent()
    {
        $this->loaded = true;
    }

    #[Computed(cache: true, seconds: 3600)]
    public function productCategories()
    {
        return ProductCategory::with(['children', 'translations'])
            ->where('show', 1)
            ->where('active', 1)
            ->get();
    }

    #[Computed(cache: true, seconds: 1800)]
    public function promotions()
    {
        return Promotion::with([
            'translations',
            'products.product' => function ($q) {
                $q->with(['translations', 'price']);
            }
        ])
            ->where('active', 1)
            ->orderBy('position')
            ->get();
    }

    #[Computed(cache: true, seconds: 3600)]
    public function sliders()
    {
        return Slider::where('active', 1)
            ->orderBy('sortable')
            ->get();
    }

    #[Computed(cache: true, seconds: 3600)]
    public function brands()
    {
        return ProductBrand::where('active', 1)
            ->where('show', 1)
            ->orderBy('sortable')
            ->get();
    }

    public function render()
    {
        Log::info('🎨 Index::render() called', [
            'event_id' => $this->eventId,
        ]);

        return view('livewire.web.main.index')->layout('livewire.web.layout');
    }
}