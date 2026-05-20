<?php

namespace App\Livewire\Dashboard\Product;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductSupplier;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, WithFileUploads;

    public string $search_query = '';
    public string $order_dir    = 'desc';
    public int $per_page        = 10;
    public bool $with_trashed   = false;
    public bool $show_web       = false;
    public bool $status_active  = false;
    public $no_stock            = null;
    public bool $unsorted       = false;
    public bool $draft          = false;

    public array $selectedProducts = [];
    public bool $selectAll         = false;
    public array $currentPageIds   = [];

    public $excel_file;

    public $category_id  = null;
    public $brand_id     = null;
    public $supplier_id  = null;

    // ✅ Quick Edit — ერთიანი modal
    public $quickEditProductId    = null;
    public $quickEditCategoryId   = null;
    public $quickEditSubcategoryId = null;
    public $quickEditBrandId      = null;
    public $quickEditRegularPrice = 0;
    public $quickEditDiscountPrice = null;
    public array $quickEditSubcategories = [];

    // ✅ Bulk actions
    public $bulkCategoryId    = null;
    public $bulkSubcategoryId = null;
    public $bulkBrandId       = null;
    public array $bulkSubcategories = [];

    protected $listeners = [
        'delete',
        'restore',
        'product-refresh' => '$refresh',
        'deleteModal',
        'restoreModal',
    ];

    protected $queryString = [
        'draft'         => ['except' => false],
        'search_query'  => ['except' => ''],
        'order_dir'     => ['except' => 'desc'],
        'per_page'      => ['except' => 10],
        'brand_id'      => ['except' => 0],
        'category_id'   => ['except' => 0],
        'supplier_id'   => ['except' => 0],
        'with_trashed'  => ['except' => false],
        'show_web'      => ['except' => false],
        'status_active' => ['except' => false],
        'no_stock'      => ['except' => false],
        'unsorted'      => ['except' => false],
    ];

    public function mount(): void
    {
        //
    }

    public function paginationView(): string
    {
        return 'livewire.dashboard.partials._pagination';
    }

    public function toggleActive($productId): void
    {
        $product = Product::findOrFail($productId);
        $product->active = !$product->active;
        if ($product->active == 0) {
            $product->show = 0;
        }
        $product->save();
        $this->dispatch('ui:success', message: 'სტატუსი განახლდა!');
    }

    public function toggleShow($productId): void
    {
        $product = Product::findOrFail($productId);
        $product->show = !$product->show;
        $product->save();
        $this->dispatch('ui:success', message: 'Show სტატუსი განახლდა!');
    }

    public function uploadExcel(): void
    {
        $this->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ], [
            'excel_file.required' => 'ფაილი აუცილებელია',
            'excel_file.mimes'    => 'მხოლოდ Excel ფაილი',
        ]);

        try {
            \App\Jobs\ProductExcelImportJob::dispatchSync(
                $this->excel_file->store('imports', 'public')
            );
            $this->dispatch('ui:success', message: 'ფაილი მიღებულია!');
            $this->reset('excel_file');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Excel upload error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეცდომა მოხდა');
        }
    }

    public function deleteModal($productId): void
    {
        $this->dispatch('swal:deleteModal', [
            'id'                => $productId,
            'title'             => 'პროდუქტის წაშლა?',
            'icon'              => 'warning',
            'confirmButtonText' => 'წაშლა!',
            'cancelButtonText'  => 'დახურვა!',
            'type'              => 'delete',
        ]);
    }

    public function restoreModal($productId): void
    {
        $this->dispatch('swal:restoreModal', [
            'id'                => $productId,
            'title'             => 'პროდუქტის აღდგენა?',
            'icon'              => 'warning',
            'confirmButtonText' => 'აღდგენა!',
            'cancelButtonText'  => 'დახურვა!',
            'type'              => 'restore',
        ]);
    }

    public function restore($id): void
    {
        Product::withTrashed()->findOrFail($id)->restore();
        $this->dispatch('ui:success', message: 'პროდუქტი აღდგა!');
    }

    public function delete($id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['active' => 0]);
        $product->delete();
        $this->dispatch('ui:success', message: 'პროდუქტი წაიშალა!');
    }

    public function applyFilters(): void
    {
        $this->resetPage();
        $this->dispatch('filter_modal_close');
    }

    public function resetFilters(): void
    {
        $this->reset(['search_query', 'order_dir', 'per_page', 'with_trashed', 'show_web', 'status_active', 'unsorted', 'supplier_id']);
        $this->resetPage();
        $this->dispatch('filter_modal_close');
    }

    public function updatedSelectAll($value): void
    {
        $this->selectedProducts = $value ? $this->currentPageIds : [];
    }

    public function updatedSelectedProducts(): void
    {
        $this->selectAll = !empty($this->currentPageIds)
            && count($this->selectedProducts) === count($this->currentPageIds);
    }

    public function updatingPage(): void
    {
        $this->selectAll        = false;
        $this->selectedProducts = [];
    }

    // ============================================
    // ✅ Quick Edit — ერთიანი modal
    // ============================================

    public function quickEditModal(int $productId): void
    {
        $product = Product::with('price')->findOrFail($productId);

        $this->quickEditProductId     = $product->id;
        $this->quickEditCategoryId    = $product->category_id;
        $this->quickEditSubcategoryId = null;
        $this->quickEditBrandId       = $product->brand_id;
        $this->quickEditRegularPrice  = $product->price->regular_price ?? 0;
        $this->quickEditDiscountPrice = $product->price->discount_price;
        $this->quickEditSubcategories = [];

        // ✅ კატეგორიის იერარქიის განსაზღვრა
        if ($product->category_id) {
            $category = ProductCategory::find($product->category_id);
            if ($category && $category->parent_id != 0) {
                $this->quickEditCategoryId    = $category->parent_id;
                $this->quickEditSubcategoryId = $category->id;
                $this->quickEditSubcategories = ProductCategory::where('parent_id', $category->parent_id)->get()->toArray();
            } else {
                $this->quickEditSubcategories = ProductCategory::where('parent_id', $product->category_id)->get()->toArray();
            }
        }

        $this->dispatch('quick_edit_modal_open');
    }

    public function updatedQuickEditCategoryId($value): void
    {
        $this->quickEditSubcategories = ProductCategory::where('parent_id', $value)->get()->toArray();
        $this->quickEditSubcategoryId = null;
    }

    public function quickEditSave(): void
    {
        $this->validate([
            'quickEditRegularPrice' => 'required|numeric|min:0',
        ], [
            'quickEditRegularPrice.required' => 'ფასი აუცილებელია',
        ]);

        $product = Product::findOrFail($this->quickEditProductId);

        $finalCategoryId = $this->quickEditSubcategoryId ?? $this->quickEditCategoryId;

        $product->update([
            'category_id' => $finalCategoryId,
            'brand_id'    => $this->quickEditBrandId,
        ]);

        ProductPrice::updateOrCreate(
            ['product_id' => $product->id],
            [
                'regular_price'  => (float) $this->quickEditRegularPrice,
                'discount_price' => !empty($this->quickEditDiscountPrice) ? (float) $this->quickEditDiscountPrice : null,
            ]
        );

        $this->quickEditProductId = null;
        $this->dispatch('quick_edit_modal_close');
        $this->dispatch('ui:success', message: 'პროდუქტი განახლდა!');
    }

    // ============================================
    // ✅ Bulk actions
    // ============================================

    public function updatedBulkCategoryId($value): void
    {
        $this->bulkSubcategories = ProductCategory::where('parent_id', $value)->get()->toArray();
        $this->bulkSubcategoryId = null;
    }

    public function bulkUpdateCategory(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('ui:error', message: 'პროდუქტი არ არის არჩეული!');
            return;
        }

        $finalCategoryId = $this->bulkSubcategoryId ?? $this->bulkCategoryId;

        if (empty($finalCategoryId)) {
            $this->dispatch('ui:error', message: 'კატეგორია აუცილებელია!');
            return;
        }

        Product::whereIn('id', $this->selectedProducts)->update(['category_id' => $finalCategoryId]);

        $this->selectedProducts = [];
        $this->selectAll        = false;
        $this->dispatch('bulk_modal_close');
        $this->dispatch('ui:success', message: 'კატეგორია განახლდა!');
    }

    public function bulkUpdateBrand(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('ui:error', message: 'პროდუქტი არ არის არჩეული!');
            return;
        }

        if (empty($this->bulkBrandId)) {
            $this->dispatch('ui:error', message: 'ბრენდი აუცილებელია!');
            return;
        }

        Product::whereIn('id', $this->selectedProducts)->update(['brand_id' => $this->bulkBrandId]);

        $this->selectedProducts = [];
        $this->selectAll        = false;
        $this->dispatch('bulk_modal_close');
        $this->dispatch('ui:success', message: 'ბრენდი განახლდა!');
    }

    public function render()
    {
        $query = Product::with(['translations'])
            ->when($this->search_query, fn($q) => $q->whereHas('translations',
                fn($subQuery) => $subQuery->where('title', 'like', "%{$this->search_query}%")
            ))
            ->when($this->show_web === true,    fn($q) => $q->where('show', $this->show_web))
            ->when($this->category_id,          fn($q) => $q->where('category_id', $this->category_id))
            ->when($this->brand_id,             fn($q) => $q->where('brand_id', $this->brand_id))
            ->when($this->supplier_id,          fn($q) => $q->where('supplier_id', $this->supplier_id))
            ->when($this->status_active === true, fn($q) => $q->where('active', $this->status_active))
            ->when($this->unsorted === true,    fn($q) => $q->whereIn('category_id', [3, 4, 182]))
            ->when($this->no_stock !== null && $this->no_stock !== '',
                fn($q) => $this->no_stock === '1'
                    ? $q->where('quantity', '>', 0)
                    : $q->where('quantity', 0)
            )
            ->when($this->draft === true,
                fn($q) => $q->where('draft', 1),
                fn($q) => $q->where('draft', 0)->orWhereNull('draft')
            )
            ->when($this->with_trashed, fn($q) => $q->withTrashed())
            ->orderBy('id', $this->order_dir);

        $products = $query->paginate($this->per_page);

        $this->currentPageIds = $products->pluck('id')->map(fn($id) => (string) $id)->toArray();

        return view('livewire.dashboard.product.index', [
            'products'   => $products,
            'brands'     => ProductBrand::where('active', 1)->get(),
            'categories' => ProductCategory::where('active', 1)->get(),
            'suppliers'  => ProductSupplier::where('active', 1)->get(),
        ])->layout('livewire.dashboard.layout');
    }
}