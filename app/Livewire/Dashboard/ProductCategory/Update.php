<?php

namespace App\Livewire\Dashboard\ProductCategory;

use App\Models\Product\ProductCategory;
use App\Models\Product\ProductCategoryTranslation;
use Illuminate\Support\Str;
use Livewire\Component;

class Update extends Component
{
    public $category_id;
    public $parent_id;
    public $title_ka;
    public $title_en;
    public $title_ru;
    public $keywords_ka;
    public $keywords_en;
    public $keywords_ru;
    public $description_ka;
    public $description_en;
    public $description_ru;
    public $parent_categories;
    public $category;

    public $alta_category_id    = null;
    public $zoommer_category_id = null;

    public function openUpdateModal($id)
    {
        $this->category = ProductCategory::with('translations')->findOrFail($id);
        $this->category_id = $this->category->id;
        $this->parent_id = $this->category->parent_id;

        $this->title_ka = $this->category->translate('ka')->title ?? '';
        $this->title_en = $this->category->translate('en')->title ?? '';
        $this->title_ru = $this->category->translate('ru')->title ?? '';

        $this->keywords_ka = $this->category->translate('ka')->keywords ?? '';
        $this->keywords_en = $this->category->translate('en')->keywords ?? '';
        $this->keywords_ru = $this->category->translate('ru')->keywords ?? '';

        $this->description_ka = $this->category->translate('ka')->description ?? '';
        $this->description_en = $this->category->translate('en')->description ?? '';
        $this->description_ru = $this->category->translate('ru')->description ?? '';

        $this->alta_category_id    = $this->category->alta_category_id;
        $this->zoommer_category_id = $this->category->zoommer_category_id;

        $this->dispatch('open-update-modal');
    }

    public function render()
    {
        $categories = ProductCategory::where('id', '!=', $this->category_id)->get();
        return view('livewire.dashboard.product-category.update', [
            'categories' => $categories,
        ])->layout('livewire.dashboard.layout');
    }
}
