<?php

namespace App\Livewire\Dashboard\ProductCategory;

use App\Models\Product\ProductCategory;
use App\Models\Product\ProductCategoryTranslation;
use Illuminate\Support\Str;
use Livewire\Component;

class Create extends Component
{
    public $title_ka, $title_en, $title_ru;
    public $description_ka, $description_en, $description_ru;
    public $keywords_ka, $keywords_en, $keywords_ru;
    public $active = true;
    public $parent_id;

    protected function rules()
    {
        return [
            'title_ka' => 'required|string|min:2',
            'title_en' => 'nullable|string|min:2',
            'title_ru' => 'nullable|string|min:2',
            'description_ka' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_ru' => 'nullable|string',
            'keywords_ka' => 'nullable|string',
            'keywords_en' => 'nullable|string',
            'keywords_ru' => 'nullable|string',
            'parent_id' => 'nullable|exists:db_product_categories,id',
        ];
    }

    protected function messages()
    {
        return [
            'required' => 'გთხოვთ შეავსოთ ყველა აუცილებელი ველი!',
            'parent_id.exists' => 'აირჩიე სწორი მშობელი კატეგორია!',
        ];
    }

    private function resetForm()
    {
        $this->reset([
            'title_ka', 'title_en', 'title_ru',
            'description_ka', 'description_en', 'description_ru',
            'keywords_ka', 'keywords_en', 'keywords_ru',
            'active', 'parent_id'
        ]);
    }

    public function save()
    {
        $this->validate();
        $category = ProductCategory::create([
            'parent_id' => $this->parent_id ?? 0,
        ]);
        $translations = [
            ['locale' => 'ka', 'title' => $this->title_ka, 'description' => $this->description_ka, 'keywords' => $this->keywords_ka],
            ['locale' => 'en', 'title' => $this->title_en, 'description' => $this->description_en, 'keywords' => $this->keywords_en],
            ['locale' => 'ru', 'title' => $this->title_ru, 'description' => $this->description_ru, 'keywords' => $this->keywords_ru],
        ];
        foreach ($translations as $t) {
            if (!empty($t['title'])) {
                $baseSlug = Str::slug($t['title'], '-');
                $slugWithId = "{$baseSlug}-{$category->id}";
                ProductCategoryTranslation::create([
                    'product_category_id' => $category->id,
                    'locale' => $t['locale'],
                    'title' => $t['title'],
                    'slug' => $slugWithId,
                    'description' => $t['description'] ?? null,
                    'keywords' => $t['keywords'] ?? null,
                ]);
            }
        }
        $this->dispatch('ui:success', message: 'კატეგორია წარმატებით შეიქმნა!', title: 'შეტყობინება');
        $this->dispatch('create_modal_close');
        $this->dispatch('category-refresh');
        $this->resetForm();
    }

    public function render()
    {
        $parent_categories = ProductCategory::where([
            'parent_id' => 0,
            'active' => 1,
        ])->where('id', '!=', 1)->get();
        return view('livewire.dashboard.product-category.create', [
            'parent_categories' => $parent_categories,
        ])->layout('livewire.dashboard.layout');
    }
}
