<?php

namespace App\Livewire\Dashboard\ProductCategory;

use App\Models\Product\ProductCategory;
use App\Models\Product\ProductCategoryTranslation;
use Illuminate\Support\Str;
use Livewire\Component;

class Update extends Component
{
    public $category_id       = null;
    public $parent_id         = 0;
    public $active            = 1;
    public $show              = 1;
    public $sortable          = null;

    public $title_ka          = '';
    public $title_en          = '';
    public $title_ru          = '';
    public $keywords_ka       = '';
    public $keywords_en       = '';
    public $keywords_ru       = '';
    public $description_ka    = '';
    public $description_en    = '';
    public $description_ru    = '';

    public $alta_category_name    = null;
    public $zoommer_category_name = null;

    protected $listeners = [
        'open-category-update-modal' => 'openCategoryUpdateModal',
    ];

    public function openCategoryUpdateModal(int $id): void
    {
        $category = ProductCategory::with('translations')->findOrFail($id);

        $this->category_id = $category->id;
        $this->parent_id   = $category->parent_id ?? 0;
        $this->active      = $category->active;
        $this->show        = $category->show;
        $this->sortable    = $category->sortable;

        $this->alta_category_name    = $category->alta_category_name;
        $this->zoommer_category_name = $category->zoommer_category_name;

        foreach ($category->translations as $translation) {
            $locale = $translation->locale;
            $this->{"title_{$locale}"}       = $translation->title ?? '';
            $this->{"keywords_{$locale}"}    = $translation->keywords ?? '';
            $this->{"description_{$locale}"} = $translation->description ?? '';
        }

        $this->dispatch('open-update-modal');
    }

    public function save(): void
    {
        $this->validate([
            'title_ka' => 'required|string|max:255',
        ], [
            'title_ka.required' => 'დასახელება ქართულად აუცილებელია',
        ]);

        try {
            $category = ProductCategory::findOrFail($this->category_id);

            $category->update([
                'parent_id'            => $this->parent_id ?? 0,
                'active'               => (int) $this->active,
                'show'                 => (int) $this->show,
                'sortable'             => $this->sortable,
                'alta_category_name'   => $this->alta_category_name ?: null,
                'zoommer_category_name' => $this->zoommer_category_name ?: null,
            ]);

            $translations = [
                'ka' => ['title' => $this->title_ka, 'keywords' => $this->keywords_ka, 'description' => $this->description_ka],
                'en' => ['title' => $this->title_en, 'keywords' => $this->keywords_en, 'description' => $this->description_en],
                'ru' => ['title' => $this->title_ru, 'keywords' => $this->keywords_ru, 'description' => $this->description_ru],
            ];

            foreach ($translations as $locale => $data) {
                if (empty($data['title'])) continue;

                $existing = ProductCategoryTranslation::where('product_category_id', $category->id)
                    ->where('locale', $locale)
                    ->first();

                $slug = $existing?->slug ?? Str::slug($data['title']) . '-' . $category->id;

                ProductCategoryTranslation::updateOrCreate(
                    ['product_category_id' => $category->id, 'locale' => $locale],
                    [
                        'title'       => $data['title'],
                        'slug'        => $slug,
                        'keywords'    => $data['keywords'] ?: null,
                        'description' => $data['description'] ?: null,
                    ]
                );
            }

            $this->dispatch('ui:success', message: 'კატეგორია განახლდა!');
            $this->dispatch('close-update-modal');
            $this->dispatch('category-refresh');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Category update error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეცდომა მოხდა');
        }
    }

    public function render()
    {
        return view('livewire.dashboard.product-category.update', [
            'categories' => ProductCategory::where('parent_id', 0)
                ->where('active', 1)
                ->with('translations')
                ->get(),
        ])->layout('livewire.dashboard.layout');
    }
}