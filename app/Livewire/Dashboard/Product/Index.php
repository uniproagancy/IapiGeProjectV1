<?php

namespace App\Livewire\Dashboard\Product;

//use App\Imports\ProductImport;
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
    public string $order_dir = 'desc';
    public int $per_page = 10;
    public bool $with_trashed = false;
    public bool $show_web = false;
    public bool $status_active = false;
    public bool $no_stock = false;
    public bool $unsorted = false;

    public array $selectedProducts = [];
    public bool $selectAll = false;
    public array $currentPageIds = [];

    public $excel_file;

    public $selectedCategory = null;
    public $selectedBrand = null;
    public $selectedSubcategory = null;
    public $subcategories = [];

    public $category_id = null;
    public $brand_id = null;
    public $supplier_id = null;

    public $priceEditProductId = null;
    public $priceEditDealerPrice = 0;
    public $priceEditRegularPrice = 0;
    public $priceEditDiscountPrice = null;

    protected $listeners = [
        'delete',
        'restore',
        'product-refresh' => '$refresh',
        'deleteModal',
        'restoreModal'
    ];

    protected $queryString = [
        'search_query' => ['except' => ''],
        'order_dir' => ['except' => 'desc'],
        'per_page' => ['except' => 10],
        'brand_id' => ['except' => 0],
        'category_id' => ['except' => 0],
        'supplier_id' => ['except' => 0],
        'with_trashed' => ['except' => false],
        'show_web' => ['except' => false],
        'status_active' => ['except' => false],
        'no_stock' => ['except' => false],
        'unsorted' => ['except' => false],
    ];

    public function mount()
    {
        $this->categories = ProductCategory::where('parent_id', 0)->where('active', 1)->get();
    }

    public function paginationView()
    {
        return 'livewire.dashboard.partials._pagination';
    }

    public function toggleActive($productId)
    {
        $product = Product::findOrFail($productId);
        $product->active = !$product->active;
        if ($product->active == 0) {
            $product->show = 0;
        }
        $product->save();
        $this->dispatch('ui:success', message: 'სტატუსი განახლდა წარმატებით!', title: 'შეტყობინება');
    }

    public function toggleShow($productId)
    {
        $product = Product::findOrFail($productId);
        $product->show = !$product->show;
        $product->save();
        $this->dispatch('ui:success', message: 'Show სტატუსი განახლდა წარმატებით!', title: 'შეტყობინება');
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
            \App\Jobs\ProductExcelImportJob::dispatch(
                $this->excel_file->store('imports', 'local')
            );

            $this->dispatch('ui:success', message: 'ფაილი მიღებულია, დამუშავება დაიწყო!');
            $this->reset('excel_file');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Excel upload error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეცდომა მოხდა');
        }
    }

    public function deleteModal($productId)
    {
        $this->dispatch('swal:deleteModal', [
            'id' => $productId,
            'title' => 'პროდუქტის წაშლა?',
            'icon' => 'warning',
            'confirmButtonText' => 'წაშლა!',
            'cancelButtonText' => 'დახურვა!',
            'type' => 'delete'
        ]);
    }

    public function restoreModal($productId)
    {
        $this->dispatch('swal:restoreModal', [
            'id' => $productId,
            'title' => 'პროდუქტის აღდგენა?',
            'icon' => 'warning',
            'confirmButtonText' => 'აღდგენა!',
            'cancelButtonText' => 'დახურვა!',
            'type' => 'restore'
        ]);
    }

    public function restore($id)
    {
        Product::withTrashed()->findOrFail($id)->restore();
        $this->dispatch('ui:success', message: 'პროდუქტი აღდგა!', title: 'შეტყობინება');
    }

    public function delete($id)
    {
        $product = Product::findOrFail($id);
        $product->update(['active' => 0]);
        $product->delete();
        $this->dispatch('ui:success', message: 'პროდუქტი წაიშალა!', title: 'შეტყობინება');
    }

    public function applyFilters()
    {
        $this->resetPage();
        $this->dispatch('filter_modal_close');
    }

    public function resetFilters()
    {
        $this->reset(['search_query', 'order_dir', 'per_page', 'with_trashed', 'show_web', 'status_active', 'unsorted','supplier_id']);
        $this->resetPage();
        $this->dispatch('filter_modal_close');
    }

    public function updatedSelectAll($value)
    {
        $this->selectedProducts = $value ? $this->currentPageIds : [];
    }

    public function updatedSelectedProducts()
    {
        $this->selectAll = !empty($this->currentPageIds)
            && count($this->selectedProducts) === count($this->currentPageIds);
    }

    public function updatingPage()
    {
        $this->selectAll = false;
        $this->selectedProducts = [];
    }

    public function updatedSelectedCategory($value)
    {
        $this->subcategories = ProductCategory::where('parent_id', $value)->get();
        $this->selectedSubcategory = null;
    }

    public function updateProductCategory()
    {
        $this->validate([
            'selectedCategory' => 'required|exists:db_product_categories,id',
        ], [
            'required' => 'გთხოვთ აირჩიოთ კატეგორია',
            'db_exists' => 'დაფიქსირდა შეცდომა!',
        ]);
        if (empty($this->selectedSubcategory)) {
            $update_category = $this->selectedCategory;
        } else {
            $update_category = $this->selectedSubcategory;
        }
        Product::whereIn('id', $this->selectedProducts)->update([
            'category_id' => $update_category,
        ]);
        $this->selectedProducts = [];
        $this->dispatch('category_modal_close');
        $this->dispatch('ui:success', message: 'Show სტატუსი განახლდა წარმატებით!', title: 'შეტყობინება');
    }

    public function updateProductBrand()
    {
        $this->validate([
            'selectedBrand' => 'required|exists:db_product_brands,id',
        ], [
            'required' => 'გთხოვთ აირჩიოთ ბრენდი',
            'db_exists' => 'დაფიქსირდა შეცდომა!',
        ]);
        Product::whereIn('id', $this->selectedProducts)->update([
            'brand_id' => $this->selectedBrand,
        ]);
        $this->selectedProducts = [];
        $this->dispatch('brand_modal_close');
        $this->dispatch('ui:success', message: 'Show სტატუსი განახლდა წარმატებით!', title: 'შეტყობინება');
    }

    public function priceEditModal($productId): void
    {
        $product = Product::with('price')->findOrFail($productId);

        $this->priceEditProductId    = $product->id;
        $this->priceEditDealerPrice  = $product->price->dealer_price ?? 0;
        $this->priceEditRegularPrice = $product->price->regular_price ?? 0;
        $this->priceEditDiscountPrice = $product->price->discount_price;

        $this->dispatch('price_edit_modal_open');
    }

    public function updatePrice(): void
    {
        $this->validate([
            'priceEditRegularPrice' => 'required|numeric|min:0',
        ], [
            'priceEditRegularPrice.required' => 'ფასი აუცილებელია',
            'priceEditRegularPrice.numeric'  => 'ფასი უნდა იყოს რიცხვი',
        ]);

        $product = Product::with('price')->findOrFail($this->priceEditProductId);

        ProductPrice::updateOrCreate(
            ['product_id' => $product->id],
            [
                'regular_price'  => (float) $this->priceEditRegularPrice,
                'discount_price' => !empty($this->priceEditDiscountPrice)
                    ? (float) $this->priceEditDiscountPrice
                    : null,
            ]
        );

        $this->priceEditProductId = null;
        $this->dispatch('price_edit_modal_close');
        $this->dispatch('ui:success', message: 'ფასი განახლდა წარმატებით!');
    }

    public function render()
    {
        $query = Product::with(['translations'])
            ->when($this->search_query, fn($q) => $q->whereHas('translations', fn($subQuery) => $subQuery->where('title', 'like', "%{$this->search_query}%")
            )
            )
            ->when($this->show_web === true, fn($q) => $q->where('show', $this->show_web)
            )
            ->when($this->category_id, fn($q) => $q->where('category_id', $this->category_id)
            )
            ->when($this->brand_id, fn($q) => $q->where('brand_id', $this->brand_id)
            )
            ->when($this->supplier_id, fn($q) => $q->where('supplier_id', $this->supplier_id)
            )
            ->when($this->status_active === true, fn($q) => $q->where('active', $this->status_active)
            )
            ->when($this->unsorted === true, fn($q) => $q->whereIn('category_id', [3,4])
            )
            ->when($this->no_stock === true, fn($q) => $q->where('in_stock', 0)
            )
            ->when($this->with_trashed, fn($q) => $q->withTrashed())
            ->orderBy('id', $this->order_dir);
        $products = $query->paginate($this->per_page);

        $this->currentPageIds = $products->pluck('id')->map(fn($id) => (string)$id)->toArray();

        return view('livewire.dashboard.product.index', [
            'products' => $products,
            'brands' => ProductBrand::where('active', 1)->get(),
            'categories' => ProductCategory::where('active', 1)->get(),
            'suppliers' => ProductSupplier::where('active', 1)->get(),
        ])->layout('livewire.dashboard.layout');
    }
}
