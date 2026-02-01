<?php

namespace App\Livewire\Dashboard\ProductBrand;

use App\Models\Product\ProductBrand;
use App\Models\Product\ProductBrandTranslation;
use Illuminate\Support\Str;
use Livewire\Component;

class Update extends Component
{
    public $brand_id;
    public $title_ka, $title_en, $title_ru;
    public $slug_ka, $slug_en, $slug_ru;
    public $active = true;
    public $showModal = false;

    protected $listeners = ['openBrandUpdateModal' => 'openModal'];

    public function openModal($brand_id)
    {
        $this->resetValidation();
        $this->brand_id = $brand_id;

        $brand = ProductBrand::with('translations')->findOrFail($brand_id);

        $this->active = $brand->active;

        $translations = $brand->translations->keyBy('locale');

        $this->title_ka = $translations['ka']->title ?? '';
        $this->title_en = $translations['en']->title ?? '';
        $this->title_ru = $translations['ru']->title ?? '';

        $this->slug_ka = $translations['ka']->slug ?? '';
        $this->slug_en = $translations['en']->slug ?? '';
        $this->slug_ru = $translations['ru']->slug ?? '';

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->resetValidation();
        $this->reset([
            'brand_id', 'title_ka', 'title_en', 'title_ru',
            'slug_ka', 'slug_en', 'slug_ru',
            'active'
        ]);
        $this->showModal = false;
    }

    protected function rules()
    {
        return [
            'title_ka' => 'required|string',
            'title_en' => 'nullable|string',
            'title_ru' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'title_ka.required' => 'ბრენდის სახელი (ქართულად) სავალდებულოა.',
        ];
    }

    public function updatedTitleKa($value)
    {
        if (!empty($value) && (empty($this->slug_ka) || $this->slug_ka === Str::slug($this->title_ka))) {
            $this->slug_ka = Str::slug($value) . '-' . $this->brand_id;
        }
    }

    public function updatedTitleEn($value)
    {
        if (!empty($value) && $this->brand_id) {
            $this->slug_en = Str::slug($value) . '-' . $this->brand_id;
        }
    }

    public function updatedTitleRu($value)
    {
        if (!empty($value) && $this->brand_id) {
            $this->slug_ru = Str::slug($value) . '-' . $this->brand_id;
        }
    }

    public function save()
    {
        $this->validate();
        $brand = ProductBrand::findOrFail($this->brand_id);
        $brand->update(['active' => $this->active]);
        $translations = [
            ['locale' => 'ka', 'title' => $this->title_ka, 'slug' => $this->slug_ka],
            ['locale' => 'en', 'title' => $this->title_en, 'slug' => $this->slug_en],
            ['locale' => 'ru', 'title' => $this->title_ru, 'slug' => $this->slug_ru],
        ];
        foreach ($translations as $t) {
            if (!empty($t['title'])) {
                ProductBrandTranslation::updateOrCreate(
                    ['product_brand_id' => $brand->id, 'locale' => $t['locale']],
                    [
                        'title' => $t['title'],
                        'slug' => $t['slug'],
                    ]
                );
            }
        }
        $this->dispatch('success', message: 'ბრენდი წარმატებით განახლდა!');
        $this->dispatch('brandUpdated');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.dashboard.product-brand.update')
            ->layout('livewire.dashboard.layout');
    }
}
