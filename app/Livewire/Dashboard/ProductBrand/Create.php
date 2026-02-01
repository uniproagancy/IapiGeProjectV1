<?php

namespace App\Livewire\Dashboard\ProductBrand;

use App\Models\Product\ProductBrand;
use App\Models\Product\ProductBrandTranslation;
use Illuminate\Support\Str;
use Livewire\Component;

class Create extends Component
{
    public $title_ka, $title_en, $title_ru;
    public $description_ka, $description_en, $description_ru;
    public $keywords_ka, $keywords_en, $keywords_ru;
    public $active = true;

    protected function rules()
    {
        return [
            'title_ka' => 'required|string|min:2',
            'title_en' => 'nullable|string|min:2',
            'title_ru' => 'nullable|string|min:2',
            'description_ka' => 'required|string',
            'description_en' => 'nullable|string',
            'description_ru' => 'nullable|string',
            'keywords_ka' => 'required|string',
            'keywords_en' => 'nullable|string',
            'keywords_ru' => 'nullable|string',
        ];
    }

    protected function messages()
    {
        return [
            'required' => 'გთხოვთ შეავსოთ ყველა აუცილებელი ველი!',
        ];
    }

    private function resetForm()
    {
        $this->reset([
            'title_ka', 'title_en', 'title_ru',
            'description_ka', 'description_en', 'description_ru',
            'keywords_ka', 'keywords_en', 'keywords_ru',
            'active'
        ]);
    }

    public function save()
    {
        $this->validate();
        $category = ProductBrand::create();
        $translations = [
            ['locale' => 'ka', 'title' => $this->title_ka, 'description' => $this->description_ka, 'keywords' => $this->keywords_ka],
            ['locale' => 'en', 'title' => $this->title_en, 'description' => $this->description_en, 'keywords' => $this->keywords_en],
            ['locale' => 'ru', 'title' => $this->title_ru, 'description' => $this->description_ru, 'keywords' => $this->keywords_ru],
        ];
        foreach ($translations as $t) {
            if (!empty($t['title'])) {
                $baseSlug = Str::slug($t['title'], '-');
                $slugWithId = "{$baseSlug}-{$category->id}";
                ProductBrandTranslation::create([
                    'product_brand_id' => $category->id,
                    'locale' => $t['locale'],
                    'title' => $t['title'],
                    'slug' => $slugWithId,
                    'description' => $t['description'] ?? null,
                    'keywords' => $t['keywords'] ?? null,
                ]);
            }
        }
        $this->dispatch('ui:success', message: 'ბრენდი წარმატებით შეიქმნა!', title: 'შეტყობინება');
//        $this->dispatch('brandCreated');
    }

    public function render()
    {
        return view('livewire.dashboard.product-brand.create')
            ->layout('livewire.dashboard.layout');
    }
}
