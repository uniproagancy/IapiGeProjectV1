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
use Illuminate\Support\Facades\Log;

class Index extends Component
{
    public string $eventId;

    // ✅ Static property to track if PageView was already sent in this request
    private static bool $pageViewTracked = false;

    public function mount()
    {
        $this->eventId = 'pv_' . time() . '_' . Str::random(6);

        Log::info('🔍 Index::mount() called', [
            'event_id' => $this->eventId,
            'already_tracked' => self::$pageViewTracked,
        ]);

        // ✅ Only track if not already tracked in this request
        if (!self::$pageViewTracked) {
            Log::info('✅ Tracking PageView NOW');

            app(FacebookPixelService::class)->trackPageViewWithTest('TEST68876', [
                'event_id' => $this->eventId,
            ]);

            // ✅ Mark as tracked
            self::$pageViewTracked = true;
        } else {
            Log::warning('⏭️ PageView ALREADY TRACKED - SKIPPING');
        }
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
        Log::info('🎨 Index::render() called', [
            'event_id' => $this->eventId,
        ]);

        return view('livewire.web.main.index', [
            'sliders' => $this->sliders,
            'brands' => $this->brands,
            'event_id' => $this->eventId,
        ])->layout('livewire.web.layout');
    }
}