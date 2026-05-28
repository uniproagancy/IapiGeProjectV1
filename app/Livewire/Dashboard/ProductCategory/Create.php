<?php

namespace App\Livewire\Dashboard\ProductCategory;

use App\Models\Product\ProductCategory;
use App\Models\Product\ProductCategoryTranslation;
use Illuminate\Support\Str;
use Livewire\Component;

class Create extends Component
{
    public $title_ka       = '';
    public $title_en       = '';
    public $title_ru       = '';
    public $description_ka = '';
    public $description_en = '';
    public $description_ru = '';
    public $keywords_ka    = '';
    public $keywords_en    = '';
    public $keywords_ru    = '';
    public $active         = 1;
    public $show           = 1;
    public $sortable       = null;
    public $parent_id      = 0;

    public $alta_category_id    = null;
    public $zoommer_category_id = null;

    protected function rules(): array
    {
        return [
            'title_ka'           => 'required|string|min:2|max:255',
            'title_en'           => 'nullable|string|min:2|max:255',
            'title_ru'           => 'nullable|string|min:2|max:255',
            'description_ka'     => 'nullable|string',
            'description_en'     => 'nullable|string',
            'description_ru'     => 'nullable|string',
            'keywords_ka'        => 'nullable|string',
            'keywords_en'        => 'nullable|string',
            'keywords_ru'        => 'nullable|string',
            'parent_id'          => 'nullable|exists:db_product_categories,id',
            'alta_category_id'   => 'nullable|integer',
            'zoommer_category_id' => 'nullable|integer',
        ];
    }

    protected function messages(): array
    {
        return [
            'title_ka.required' => 'დასახელება ქართულად აუცილებელია',
            'title_ka.min'      => 'მინიმუმ 2 სიმბოლო',
            'parent_id.exists'  => 'სწორი მშობელი კატეგორია აირჩიეთ',
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'title_ka', 'title_en', 'title_ru',
            'description_ka', 'description_en', 'description_ru',
            'keywords_ka', 'keywords_en', 'keywords_ru',
            'sortable', 'alta_category_id', 'zoommer_category_id',
        ]);
        $this->active    = 1;
        $this->show      = 1;
        $this->parent_id = 0;
    }

    public function save(): void
    {
        $this->validate();

        try {
            $category = ProductCategory::create([
                'parent_id'          => $this->parent_id ?? 0,
                'active'             => (int) $this->active,
                'show'               => (int) $this->show,
                'sortable'           => $this->sortable,
                'alta_category_id'   => $this->alta_category_id ?: null,
                'zoommer_category_id' => $this->zoommer_category_id ?: null,
            ]);

            $translations = [
                'ka' => ['title' => $this->title_ka, 'description' => $this->description_ka, 'keywords' => $this->keywords_ka],
                'en' => ['title' => $this->title_en, 'description' => $this->description_en, 'keywords' => $this->keywords_en],
                'ru' => ['title' => $this->title_ru, 'description' => $this->description_ru, 'keywords' => $this->keywords_ru],
            ];

            foreach ($translations as $locale => $data) {
                if (empty($data['title'])) continue;

                $slug = Str::slug($data['title']) . '-' . $category->id;

                ProductCategoryTranslation::create([
                    'product_category_id' => $category->id,
                    'locale'              => $locale,
                    'title'               => $data['title'],
                    'slug'                => $slug,
                    'description'         => $data['description'] ?: null,
                    'keywords'            => $data['keywords'] ?: null,
                ]);
            }

            $this->dispatch('ui:success', message: 'კატეგორია წარმატებით შეიქმნა!');
            $this->dispatch('create_modal_close');
            $this->dispatch('category-refresh');
            $this->resetForm();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Category create error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეცდომა მოხდა');
        }
    }

    public function render()
    {
        return view('livewire.dashboard.product-category.create', [
            'parent_categories' => ProductCategory::where('parent_id', 0)
                ->where('active', 1)
                ->where('id', '!=', 1)
                ->with('translations')
                ->get(),
        ])->layout('livewire.dashboard.layout');
    }
}