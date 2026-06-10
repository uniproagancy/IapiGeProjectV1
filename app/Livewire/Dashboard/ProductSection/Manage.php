<?php

namespace App\Livewire\Dashboard\ProductSection;

use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductSection;
use Livewire\Component;
use Livewire\WithPagination;

class Manage extends Component
{
    use WithPagination;

    public ProductSection $section;
    public string $search = '';

    public $mainCategoryId = null;
    public $subCategoryId  = null;

    public array $selected = [];
    public bool $selectAll = false;

    public function mount(int $id): void
    {
        $this->section = ProductSection::with('products')->findOrFail($id);
    }

    public function updatedMainCategoryId(): void
    {
        $this->subCategoryId = null;
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    public function updatedSubCategoryId(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            // მიმდინარე გვერდის ხილული პროდუქტები
            $this->selected = $this->getResults()->pluck('id')->map(fn($i) => (string) $i)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function addProduct(int $productId): void
    {
        if ($this->section->products()->where('product_id', $productId)->exists()) {
            $this->dispatch('ui:error', message: 'პროდუქტი უკვე დამატებულია!');
            return;
        }

        $maxOrder = $this->section->products()->max('db_product_section_items.sort_order') ?? 0;
        $this->section->products()->attach($productId, ['sort_order' => $maxOrder + 1]);

        $this->section->load('products');
        $this->dispatch('ui:success', message: 'დაემატა!');
    }

    public function addSelected(): void
    {
        if (empty($this->selected)) {
            $this->dispatch('ui:error', message: 'არცერთი არ არის მონიშნული!');
            return;
        }

        $existingIds = $this->section->products->pluck('id')->toArray();
        $maxOrder    = $this->section->products()->max('db_product_section_items.sort_order') ?? 0;

        $added  = 0;
        $attach = [];
        foreach ($this->selected as $pid) {
            $pid = (int) $pid;
            if (in_array($pid, $existingIds)) {
                continue;
            }
            $attach[$pid] = ['sort_order' => ++$maxOrder];
            $added++;
        }

        if (!empty($attach)) {
            $this->section->products()->attach($attach);
        }

        $this->section->load('products');
        $this->selected = [];
        $this->selectAll = false;
        $this->dispatch('ui:success', message: "{$added} პროდუქტი დაემატა!");
    }

    public function removeProduct(int $productId): void
    {
        $this->section->products()->detach($productId);
        $this->section->load('products');
        $this->dispatch('ui:success', message: 'მოშორდა!');
    }

    // query builder (paginate-ისთვის და selectAll-ისთვის)
    private function buildQuery()
    {
        $addedIds = $this->section->products->pluck('id')->toArray();

        $query = Product::query()
            ->whereNotIn('id', $addedIds)
            ->with('translations')
            ->orderBy('id', 'desc');

        // კატეგორიის ფილტრი
        if ($this->subCategoryId) {
            $query->where('category_id', $this->subCategoryId);
        } elseif ($this->mainCategoryId) {
            $subIds = ProductCategory::where('parent_id', $this->mainCategoryId)->pluck('id')->toArray();
            $catIds = array_merge([$this->mainCategoryId], $subIds);
            $query->whereIn('category_id', $catIds);
        }

        // ძებნა (ნებისმიერი სიგრძის)
        if (trim($this->search) !== '') {
            $query->where(function ($q) {
                $q->where('sku', 'like', "%{$this->search}%")
                    ->orWhereHas('translations', fn($t) =>
                    $t->where('locale', 'ka')->where('title', 'like', "%{$this->search}%")
                    );
            });
        }

        return $query;
    }

    // ხილული შედეგი (selectAll-ისთვის — მიმდინარე გვერდი)
    private function getResults()
    {
        return $this->buildQuery()->limit(20)->get();
    }

    public function render()
    {
        $hasFilter = $this->subCategoryId || $this->mainCategoryId || trim($this->search) !== '';

        $results = $hasFilter
            ? $this->buildQuery()->paginate(20)
            : null;

        $mainCategories = ProductCategory::where('parent_id', 0)
            ->where('active', 1)
            ->with('translations')
            ->get();

        $subCategories = collect();
        if ($this->mainCategoryId) {
            $subCategories = ProductCategory::where('parent_id', $this->mainCategoryId)
                ->where('active', 1)
                ->with('translations')
                ->get();
        }

        return view('livewire.dashboard.product-section.manage', [
            'results'        => $results,
            'mainCategories' => $mainCategories,
            'subCategories'  => $subCategories,
        ])->layout('livewire.dashboard.layout');
    }
}