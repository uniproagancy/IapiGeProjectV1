<?php

namespace App\Livewire\Web\Product;

use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductFullSpecificationSection;
use App\Services\Facebook\FacebookPixelService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    // ============================================
    // Properties
    // ============================================

    public $parentCategories = [];
    public $subCategories = [];
    public $currentCategory = null;
    public $selectedParent = null;
    public $showAllBrands = false;
    public $category_slug = null;
    public $perPage = 20;
    public $isLoading = false;
    public string $eventId; // ✅ Added

    #[Url]
    public $search = '';

    #[Url(as: 'brands', keep: true)]
    public $selectedBrands = [];

    #[Url(as: 'min', keep: true)]
    public $priceMin = null;

    #[Url(as: 'max', keep: true)]
    public $priceMax = null;

    #[Url(as: 'specs', keep: true)]
    public $selectedSpecs = [];

    // ✅ ADD DISCOUNT FILTER PROPERTY
    #[Url]
    public bool $onlyDiscounted = false;

    // ============================================
    // Lifecycle Hooks
    // ============================================

    public function mount($category_slug = null): void
    {
        $this->category_slug = $category_slug;
        $this->loadParentCategories();
        $this->loadCategoryFromSlug();
        $this->normalizeBrands();
        $this->normalizeSpecs();

        // ✅ Track Category View (only if category is loaded)
        $this->trackCategoryView();
    }

    // ============================================
    // Facebook Pixel Tracking
    // ============================================

    private function trackCategoryView(): void
    {
        // ✅ Only track if viewing a specific category
        if (empty($this->currentCategory)) {
            Log::info('🔍 Product Index - No category selected, skipping CategoryView event');
            return;
        }

        // ✅ Generate event_id
        $this->eventId = 'cv_' . time() . '_' . Str::random(6);

        Log::info('🔍 Category View mounted', [
            'category_id' => $this->currentCategory->id,
            'category_name' => $this->currentCategory->name,
            'category_slug' => $this->category_slug,
            'event_id' => $this->eventId,
        ]);

        // ✅ Track Custom Event: CategoryView with TEST CODE
        app(FacebookPixelService::class)->trackCustomEventWithTest('TEST86097','CategoryView', [
            'content_name' => $this->currentCategory->name,
            'content_category' => $this->category_slug,
            'content_ids' => [$this->currentCategory->id],
        ],  $this->eventId);
    }

    // ============================================
    // Helper Methods - Normalization
    // ============================================

    private function normalizeBrands(): void
    {
        if (is_string($this->selectedBrands)) {
            $this->selectedBrands = $this->selectedBrands
                ? explode(',', $this->selectedBrands)
                : [];
        }

        $this->selectedBrands = array_map('intval', (array)$this->selectedBrands);
        $this->selectedBrands = array_filter($this->selectedBrands);
    }

    private function normalizeSpecs(): void
    {
        if (is_string($this->selectedSpecs)) {
            $this->selectedSpecs = $this->selectedSpecs
                ? explode(',', $this->selectedSpecs)
                : [];
        }

        $this->selectedSpecs = array_filter($this->selectedSpecs);
    }

    // ============================================
    // Helper Methods - Category Loading
    // ============================================

    private function loadParentCategories(): void
    {
        $this->parentCategories = ProductCategory::query()
            ->where('parent_id', 0)
            ->where('show', 1)
            ->get();
    }

    private function loadCategoryFromSlug(): void
    {
        if (empty($this->category_slug)) {
            return;
        }

        $category = ProductCategory::query()
            ->whereHas('translations', function ($query) {
                $query->where('slug', $this->category_slug);
            })
            ->first();

        if (!$category) {
            return;
        }

        $this->currentCategory = $category;
        $this->loadSubcategories($category);
    }

    private function loadSubcategories(ProductCategory $category): void
    {
        if ($category->parent_id === 0) {
            $this->selectedParent = $category->id;
            $this->subCategories = $category->children()
                ->where('show', 1)
                ->get();
        } else {
            $this->selectedParent = $category->parent_id;
            $this->subCategories = ProductCategory::query()
                ->where('parent_id', $category->parent_id)
                ->where('show', 1)
                ->get();
        }
    }

    // ============================================
    // Category Selection Actions
    // ============================================

    public function toggleShowAllBrands(): void
    {
        $this->showAllBrands = !$this->showAllBrands;
    }

    public function selectParent($id): void
    {
        $this->selectCategoryAndRedirect($id);
    }

    public function selectChild($id): void
    {
        $this->selectCategoryAndRedirect($id);
    }

    private function selectCategoryAndRedirect($categoryId): void
    {
        $category = ProductCategory::find($categoryId);

        if (!$category) {
            return;
        }

        $this->isLoading = true;
        $this->redirectToCategory($category);
    }

    public function resetCategories(): void
    {
        $this->isLoading = true;
        $this->redirect(route('web.products.index'), navigate: true);
    }

    private function redirectToCategory(ProductCategory $category): void
    {
        $params = $this->buildFilterParams();
        $url = route('web.products.index', [
            'category_slug' => $category->translation('ka')->slug
        ]);

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $this->redirect($url, navigate: true);
    }

    private function buildFilterParams(): array
    {
        return array_filter([
            'brands' => !empty($this->selectedBrands) ? implode(',', $this->selectedBrands) : null,
            'min' => $this->priceMin,
            'max' => $this->priceMax,
            'search' => !empty($this->search) ? $this->search : null,
            'specs' => !empty($this->selectedSpecs) ? implode(',', $this->selectedSpecs) : null,
            // ✅ ADD DISCOUNT FILTER TO PARAMS
            'onlyDiscounted' => $this->onlyDiscounted ? '1' : null,
        ]);
    }

    // ============================================
    // Filter Actions
    // ============================================

    public function clearPriceFilter(): void
    {
        $this->priceMin = null;
        $this->priceMax = null;
        $this->resetPage();
        $this->isLoading = true;
    }

    public function clearBrandFilter(): void
    {
        $this->selectedBrands = [];
        $this->resetPage();
        $this->isLoading = true;
    }

    public function clearSpecFilter(): void
    {
        $this->selectedSpecs = [];
        $this->resetPage();
        $this->isLoading = true;
    }

    // ✅ ADD CLEAR DISCOUNT FILTER
    public function clearDiscountFilter(): void
    {
        $this->onlyDiscounted = false;
        $this->resetPage();
        $this->isLoading = true;
    }

    public function resetAllFilters(): void
    {
        $this->selectedBrands = [];
        $this->priceMin = null;
        $this->priceMax = null;
        $this->search = '';
        $this->selectedSpecs = [];
        $this->onlyDiscounted = false; // ✅ ADD THIS
        $this->currentCategory = null;
        $this->selectedParent = null;
        $this->resetPage();
        $this->isLoading = true;
    }

    // ============================================
    // Livewire Property Updates
    // ============================================

    public function updatedSelectedBrands(): void
    {
        $this->normalizeBrands();
        $this->isLoading = true;
        $this->resetPage();
    }

    public function updatedPriceMin(): void
    {
        $this->isLoading = true;
        $this->resetPage();
    }

    public function updatedPriceMax(): void
    {
        $this->isLoading = true;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->isLoading = true;
        $this->resetPage();
    }

    public function updatedSelectedSpecs(): void
    {
        $this->normalizeSpecs();
        $this->isLoading = true;
        $this->resetPage();
    }

    // ✅ ADD DISCOUNT FILTER UPDATE
    public function updatedOnlyDiscounted(): void
    {
        $this->isLoading = true;
        $this->resetPage();
    }

    // ============================================
    // Pagination
    // ============================================

    public function loadMore(): void
    {
        $this->perPage += 40;
    }

    // ============================================
    // Computed Properties
    // ============================================

    #[Computed]
    public function products()
    {
        $query = $this->buildProductQuery();
        $result = $query
            ->orderBy('id', 'DESC')
            ->paginate($this->perPage);

        $this->isLoading = false;

        return $result;
    }

    /**
     * ✅ FIX: Don't apply brand filter to brands list
     * This way all available brands stay visible even when some are selected
     */
    #[Computed]
    public function brands()
    {
        $query = Product::query()
            ->with('translations')
            ->where('show', 1)
            ->where('active', 1);

        $this->applyCategoryFilter($query);
        $this->applyPriceFilter($query);
        $this->applySearchFilter($query);
        // ✅ ADD DISCOUNT FILTER
        $this->applyDiscountFilter($query);

        $brandIds = $query
            ->distinct('brand_id')
            ->pluck('brand_id');

        return ProductBrand::query()
            ->whereIn('id', $brandIds)
            ->where('show', 1)
            ->get();
    }

    /**
     * ✅ Get specification sections with filters (NO DUPLICATES)
     */
    #[Computed]
    public function specificationSections()
    {
        return ProductFullSpecificationSection::with('filter')
            ->get()
            ->filter(function ($section) {
                return $section->filter->isNotEmpty();
            })
            ->map(function ($section) {
                // ✅ Group by name
                $section->filter = $section->filter->groupBy('name');

                // ✅ Remove duplicate values within each filter group
                $section->filter = $section->filter->map(function ($items) {
                    return $items
                        ->unique('value')  // Remove duplicate values
                        ->values();        // Reindex array
                });

                return $section;
            })
            ->groupBy('name');  // Group sections by name for display
    }

    // ============================================
    // Query Builders
    // ============================================

    private function buildProductQuery()
    {
        $query = Product::query()
            ->with('translations')
            ->where('show', 1)
            ->where('active', 1);

        $this->applyAllFilters($query);

        return $query;
    }

    private function applyAllFilters($query): void
    {
        $this->applyCategoryFilter($query);
        $this->applyBrandFilter($query);
        $this->applyPriceFilter($query);
        $this->applySearchFilter($query);
        $this->applyDiscountFilter($query); // ✅ ADD THIS
    }

    private function applyCategoryFilter($query): void
    {
        if (empty($this->currentCategory)) {
            return;
        }
        if ($this->currentCategory->parent_id === 0) {
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

    private function applyBrandFilter($query): void
    {
        if (empty($this->selectedBrands)) {
            return;
        }

        $query->whereIn('brand_id', $this->selectedBrands);
    }

    private function applyPriceFilter($query): void
    {
        if (empty($this->priceMin) && empty($this->priceMax)) {
            return;
        }

        $query->whereHas('price', function ($priceQuery) {
            if (!empty($this->priceMin)) {
                $minPrice = round((float)$this->priceMin, 2);
                $priceQuery->where('regular_price', '>=', $minPrice);
            }

            if (!empty($this->priceMax)) {
                $maxPrice = round((float)$this->priceMax, 2);
                $priceQuery->where('regular_price', '<=', $maxPrice);
            }
        });
    }

    private function applySearchFilter($query): void
    {
        if (empty($this->search)) {
            return;
        }
        $searchTerm = "%{$this->search}%";
        $query->where(function ($q) use ($searchTerm) {
            $q->where('id', 'like', $searchTerm)
                ->orWhereHas('translations', function ($subQuery) use ($searchTerm) {
                    $subQuery->where('title', 'like', $searchTerm)
                        ->orWhere('description', 'like', $searchTerm);
                });
        });
    }

    // ✅ ADD DISCOUNT FILTER METHOD
    private function applyDiscountFilter($query): void
    {
        if (!$this->onlyDiscounted) {
            return;
        }

        $query->whereHas('price', function ($priceQuery) {
            $priceQuery->where('discount_price', '!=', 0)
                ->where('discount_price', '!=', null);
        });
    }

    // ============================================
    // Rendering
    // ============================================

    public function render()
    {
        return view('livewire.web.product.index', [
            'products' => $this->products,
            'brands' => $this->brands,
            'specificationSections' => $this->specificationSections,
            'isLoading' => $this->isLoading,
            'event_id' => $this->eventId ?? null, // ✅ Pass to view
        ])->layout('livewire.web.layout');
    }
}