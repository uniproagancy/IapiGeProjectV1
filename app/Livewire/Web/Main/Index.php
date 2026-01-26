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

class Index extends Component
{

    protected $google;
    public $spreadsheetId = '';
    public $sheetData;
    public $range = 'Sheet1!A1:C10';

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

//        $this->google = new GoogleSheet();
//        $this->spreadsheetId = '1YvGwk6pilN_S4O9ZYP4-CRxgygPLSjPS';
//        $this->sheetData = $this->google->getValues(
//            $this->spreadsheetId,
//            $this->range
//        ) ?? [];
//
//        if (empty($this->sheetData)) {
//            dd(123);
//        } else {
//            $this->dispatch('ui:success', message: 'Data loaded successfully! Rows: ' . count($this->sheetData));
//        }
//        dd($this->google);

        return view('livewire.web.main.index', [
            'sliders',
            'brands'
        ])->layout('livewire.web.layout');
    }
}