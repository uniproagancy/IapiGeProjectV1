<?php

namespace App\Livewire\Web\Product;

use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductBrand;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;

class Index extends Component
{
    use WithPagination;

    public $parentCategories;
    public $subCategories = [];

    public $currentCategory = null;
    public $selectedParent = null;
    public $showAllBrands = false;
    public $category_slug = null;
    public $perPage = 20;

    #[Url(as: 'brands', keep: true)]
    public $selectedBrands = [];

    #[Url(as: 'min', keep: true)]
    public $priceMin = null;

    #[Url(as: 'max', keep: true)]
    public $priceMax = null;

    public function mount($category_slug = null)
    {
        $this->category_slug = $category_slug;
        $this->loadParentCategories();
        $this->loadCategoryFromSlug();
    }

    public function toggleShowAllBrands()
    {
        $this->showAllBrands = !$this->showAllBrands;
    }

    private function loadParentCategories()
    {
        $this->parentCategories = ProductCategory::where('parent_id', 0)
            ->where('show', 1)
            ->get();
    }

    private function loadCategoryFromSlug()
    {
        if (!$this->category_slug) {
            return;
        }

        $category = ProductCategory::whereHas('translations', function ($q) {
            $q->where('slug', $this->category_slug);
        })->first();
        if (!$category) {
            return;
        }
        $this->currentCategory = $category;
        if ($category->parent_id == 0) {
            $this->selectedParent = $category->id;
            $this->subCategories = $category->children()
                ->where('show', 1)
                ->get();
        } else {
            $this->selectedParent = $category->parent_id;
            $this->subCategories = ProductCategory::where('parent_id', $category->parent_id)
                ->where('show', 1)
                ->get();
        }
    }

    public function selectParent($id)
    {
        $category = ProductCategory::find($id);
        if (!$category) {
            return;
        }
        return $this->redirectToCategory($category);
    }

    public function selectChild($id)
    {
        $category = ProductCategory::find($id);
        if (!$category) {
            return;
        }
        return $this->redirectToCategory($category);
    }

    public function resetCategories()
    {
        return $this->redirect(route('web.products.index'), navigate: true);
    }

    private function redirectToCategory($category)
    {
        $params = array_filter([
            'brands' => !empty($this->selectedBrands) ? implode(',', $this->selectedBrands) : null,
            'min' => $this->priceMin,
            'max' => $this->priceMax,
        ]);
        $url = route('web.products.index', [
            'category_slug' => $category->translation('ka')->slug
        ]);
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $this->redirect($url, navigate: true);
    }

    public function updatedSelectedBrands()
    {
        $this->resetPage();
    }

    public function updatedPriceMin()
    {
        $this->resetPage();
    }

    public function updatedPriceMax()
    {
        $this->resetPage();
    }

    #[Computed]
    public function products()
    {
        $query = $this->buildProductQuery();
        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function brands()
    {
        $query = Product::query()
            ->where('active', 1)
            ->where('show', 1);
        if ($this->currentCategory) {
            if ($this->currentCategory->parent_id == 0) {
                $childIds = $this->currentCategory->children()
                    ->whereHas('products', function ($q) {
                        $q->where('active', 1)->where('show', 1);
                    })
                    ->pluck('id');
                $query->whereIn('category_id', $childIds);
            } else {
                $query->where('category_id', $this->currentCategory->id);
            }
        }

        if (!empty($this->priceMin) || !empty($this->priceMax)) {
            $query->whereHas('price', function ($p) {
                if (!empty($this->priceMin)) {
                    $p->where('regular_price', '>=', $this->priceMin);
                }
                if (!empty($this->priceMax)) {
                    $p->where('regular_price', '<=', $this->priceMax);
                }
            });
        }
        $brandIds = $query->pluck('brand_id')->unique();
        return ProductBrand::whereIn('id', $brandIds)
            ->where('show', 1)
            ->get();
    }

    private function buildProductQuery()
    {
        $query = Product::query();
        if ($this->currentCategory) {
            if ($this->currentCategory->parent_id == 0) {
                $childIds = $this->currentCategory->children()
                    ->whereHas('products', function ($q) {
                        $q->where('active', 1)->where('show', 1);
                    })
                    ->pluck('id');
                $query->whereIn('category_id', $childIds);
            } else {
                $query->where('category_id', $this->currentCategory->id);
            }
        }
        $query->where('active', 1)
            ->where('show', 1);
        if (!empty($this->selectedBrands)) {
            $query->whereIn('brand_id', $this->selectedBrands);
        }
        if (!empty($this->priceMin) || !empty($this->priceMax)) {
            $query->whereHas('price', function ($p) {
                if (!empty($this->priceMin)) {
                    $p->where('regular_price', '>=', $this->priceMin);
                }
                if (!empty($this->priceMax)) {
                    $p->where('regular_price', '<=', $this->priceMax);
                }
            });
        }
        return $query;
    }

    public function loadMore()
    {
        $this->perPage += 12;
    }

    public function render()
    {
        return view('livewire.web.product.index', [
            'products' => $this->products,
            'brands' => $this->brands,
        ])->layout('livewire.web.layout');
    }
}