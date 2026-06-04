<?php

namespace App\Livewire\Dashboard\Product;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductSupplier;
use App\Services\Products\GlobalService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public $global_file;

    public $selectedCategory    = null;
    public $selectedBrand       = null;
    public $selectedSubcategory = null;
    public $subcategories       = [];

    public $category_id  = null;
    public $brand_id     = null;
    public $supplier_id  = null;

    public $priceEditProductId    = null;
    public $priceEditDealerPrice  = 0;
    public $priceEditRegularPrice = 0;
    public $priceEditDiscountPrice = null;

    // ✅ Quick Edit
    public $quickEditProductId     = null;
    public $quickEditCategoryId    = null;
    public $quickEditSubcategoryId = null;
    public $quickEditBrandId       = null;
    public $quickEditRegularPrice  = 0;
    public $quickEditDiscountPrice = null;
    public array $quickEditSubcategories = [];

    // ✅ Bulk actions
    public $bulkCategoryId    = null;
    public $bulkSubcategoryId = null;
    public $bulkBrandId       = null;
    public array $bulkSubcategories = [];

    public bool $only_locked = false;

    public bool $no_brand = false;

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
        'no_brand'      => ['except' => false],
        'only_locked'   => ['except' => false],
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
        if ($product->active == 0) $product->show = 0;
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

    public function uploadGlobal(): void
    {
        $this->validate([
            'global_file' => 'required|file|mimes:xlsx,xls,csv',
        ], [
            'global_file.required' => 'ფაილი აუცილებელია',
            'global_file.mimes'    => 'მხოლოდ Excel ან CSV ფაილი',
        ]);

        try {
            $path        = $this->global_file->getRealPath();
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

            $items = [];
            foreach ($rows as $row) {
                $name = trim((string) ($row[0] ?? ''));

                if ($name === '') continue;
                if (in_array(mb_strtolower($name), ['item', 'დასახელება', 'name', 'სახელი', 'პროდუქტი'])) continue;
                if (mb_strlen($name) < 3) continue;

                $name  = preg_replace('/\s+/u', ' ', $name);

                $stock = $this->parseStock($row[1] ?? null);  // B
                $price = $this->parsePrice($row[2] ?? null);   // C — ფასი

                $items[$name] = ['stock' => $stock, 'price' => $price];
            }

            if (empty($items)) {
                $this->dispatch('ui:error', message: 'დასახელებები ვერ მოიძებნა');
                return;
            }

            Log::info('📂 Global upload: ' . count($items) . ' row, queue-ში იგზავნება');

            \App\Models\Product\GlobalNotFound::truncate();
            Log::info('🗑️ global_not_found გასუფთავდა');

            foreach ($items as $name => $info) {
                \App\Jobs\GlobalProductJob::dispatch($name, $info['stock'], $info['price'])->onQueue('global');
                Log::info("📤 Global job dispatched: '{$name}' (stock={$info['stock']}, price={$info['price']})");
            }

            $this->reset('global_file');
            $this->dispatch('ui:success',
                message: count($items) . ' პროდუქტი queue-ში გაიგზავნა. დამუშავება ფონურად მიმდინარეობს.');

        } catch (\Throwable $e) {
            Log::error('Global upload error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეცდომა: ' . $e->getMessage());
        }
    }

    private function parsePrice($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $clean = preg_replace('/[^0-9.]/', '', (string) $value);
        return $clean === '' ? null : (float) $clean;
    }

    /**
     * Stock-ის პარსინგი: "5+", "10+", "5", "" → int
     */
    private function parseStock($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        // მხოლოდ ციფრები (+ და სხვა სიმბოლოები მოშორდეს)
        $digits = preg_replace('/[^0-9]/', '', (string) $value);
        return $digits === '' ? 0 : (int) $digits;
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
        $this->reset(['search_query', 'no_brand', 'order_dir', 'per_page', 'with_trashed', 'show_web', 'status_active', 'unsorted', 'supplier_id']);
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

    public function updatedSelectedCategory($value): void
    {
        $this->subcategories       = ProductCategory::where('parent_id', $value)->get();
        $this->selectedSubcategory = null;
    }

    public function updateProductCategory(): void
    {
        $this->validate([
            'selectedCategory' => 'required|exists:db_product_categories,id',
        ]);

        $categoryId = $this->selectedSubcategory ?? $this->selectedCategory;
        Product::whereIn('id', $this->selectedProducts)->update(['category_id' => $categoryId]);
        $this->selectedProducts = [];
        $this->dispatch('category_modal_close');
        $this->dispatch('ui:success', message: 'კატეგორია განახლდა!');
    }

    public function updateProductBrand(): void
    {
        $this->validate([
            'selectedBrand' => 'required|exists:db_product_brands,id',
        ]);

        Product::whereIn('id', $this->selectedProducts)->update(['brand_id' => $this->selectedBrand]);
        $this->selectedProducts = [];
        $this->dispatch('brand_modal_close');
        $this->dispatch('ui:success', message: 'ბრენდი განახლდა!');
    }

    public function priceEditModal($productId): void
    {
        $product = Product::with('price')->findOrFail($productId);
        $this->priceEditProductId     = $product->id;
        $this->priceEditDealerPrice   = $product->price->dealer_price ?? 0;
        $this->priceEditRegularPrice  = $product->price->regular_price ?? 0;
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
        $this->dispatch('ui:success', message: 'ფასი განახლდა!');
    }

    // ============================================
    // ✅ Quick Edit
    // ============================================

    public function quickEditModal(int $productId): void
    {
        $product = Product::with('price')->findOrFail($productId);

        $this->quickEditProductId     = $product->id;
        $this->quickEditBrandId       = $product->brand_id;
        $this->quickEditRegularPrice  = $product->price->regular_price ?? 0;
        $this->quickEditDiscountPrice = $product->price->discount_price;
        $this->quickEditSubcategories = [];
        $this->quickEditCategoryId    = null;
        $this->quickEditSubcategoryId = null;

        if ($product->category_id) {
            $category = ProductCategory::find($product->category_id);
            if ($category) {
                if ($category->parent_id == 0 || $category->parent_id === null) {
                    $this->quickEditCategoryId    = $category->id;
                    $this->quickEditSubcategories = ProductCategory::where('parent_id', $category->id)->get()->toArray();
                } else {
                    $this->quickEditCategoryId    = $category->parent_id;
                    $this->quickEditSubcategoryId = $category->id;
                    $this->quickEditSubcategories = ProductCategory::where('parent_id', $category->parent_id)->get()->toArray();
                }
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

        $product         = Product::findOrFail($this->quickEditProductId);
        $finalCategoryId = $this->quickEditSubcategoryId ?? $this->quickEditCategoryId;

        $product->update([
            'category_id' => $finalCategoryId,
            'brand_id'    => $this->quickEditBrandId,
        ]);

        ProductPrice::updateOrCreate(
            ['product_id' => $product->id],
            [
                'regular_price'  => (float) $this->quickEditRegularPrice,
                'discount_price' => !empty($this->quickEditDiscountPrice)
                    ? (float) $this->quickEditDiscountPrice
                    : null,
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

    public function bulkDelete(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('ui:error', message: 'პროდუქტი არ არის არჩეული!');
            return;
        }

        $count = count($this->selectedProducts);
        Product::whereIn('id', $this->selectedProducts)->update(['active' => 0]);
        Product::whereIn('id', $this->selectedProducts)->delete();

        $this->selectedProducts = [];
        $this->selectAll        = false;
        $this->dispatch('bulk_modal_close');
        $this->dispatch('ui:success', message: "{$count} პროდუქტი წაიშალა!");
    }

    public function bulkRestore(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('ui:error', message: 'პროდუქტი არ არის არჩეული!');
            return;
        }

        $count = count($this->selectedProducts);
        Product::withTrashed()->whereIn('id', $this->selectedProducts)->restore();

        $this->selectedProducts = [];
        $this->selectAll        = false;
        $this->dispatch('bulk_modal_close');
        $this->dispatch('ui:success', message: "{$count} პროდუქტი აღდგა!");
    }

    public function toggleLock($productId): void
    {
        $product = Product::findOrFail($productId);
        $product->update_lock = !$product->update_lock;
        $product->save();
        $this->dispatch('ui:success', message: $product->update_lock
            ? 'პროდუქტი ჩაიკეტა (განახლება გათიშულია)'
            : 'პროდუქტი განიბლოკა');
    }

    public function bulkLock(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('ui:error', message: 'პროდუქტი არ არის არჩეული!');
            return;
        }

        $count = count($this->selectedProducts);
        Product::whereIn('id', $this->selectedProducts)->update(['update_lock' => 1]);
        $this->selectedProducts = [];
        $this->selectAll        = false;
        $this->dispatch('bulk_modal_close');
        $this->dispatch('ui:success', message: "{$count} პროდუქტი ჩაიკეტა!");
    }

    public function bulkUnlock(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('ui:error', message: 'პროდუქტი არ არის არჩეული!');
            return;
        }

        $count = count($this->selectedProducts);
        Product::whereIn('id', $this->selectedProducts)->update(['update_lock' => 0]);
        $this->selectedProducts = [];
        $this->selectAll        = false;
        $this->dispatch('bulk_modal_close');
        $this->dispatch('ui:success', message: "{$count} პროდუქტი განიბლოკა!");
    }

    public function exportNotFound(): StreamedResponse
    {
        $rows = \App\Models\Product\GlobalNotFound::orderBy('name')->get();

        $filename = 'not_found_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM — ქართული რომ სწორად გაიხსნას Excel-ში
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Name', 'Stock', 'Price', 'Reason', 'Date']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->name, $r->stock, $r->price, $r->reason, $r->created_at]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ============================================
    // Render
    // ============================================

    public function render()
    {
        $query = Product::with(['translations'])
            ->when($this->search_query, fn($q) => $q->whereHas('translations',
                fn($subQuery) => $subQuery->where('title', 'like', "%{$this->search_query}%")
            ))
            ->when($this->show_web === true,      fn($q) => $q->where('show', $this->show_web))
            ->when($this->category_id,            fn($q) => $q->where('category_id', $this->category_id))
            ->when($this->brand_id,               fn($q) => $q->where('brand_id', $this->brand_id))
            ->when($this->supplier_id,            fn($q) => $q->where('supplier_id', $this->supplier_id))
            ->when($this->status_active === true, fn($q) => $q->where('active', $this->status_active))
            ->when($this->unsorted === true,      fn($q) => $q->whereIn('category_id', [3, 4, 182]))
            ->when($this->no_brand === true,      fn($q) => $q->whereIn('brand_id', [1, 6]))
            ->when($this->only_locked === true,   fn($q) => $q->where('update_lock', 1))
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
            'categories' => ProductCategory::where('parent_id', 0)->where('active', 1)->get(),
            'suppliers'  => ProductSupplier::where('active', 1)->get(),
        ])->layout('livewire.dashboard.layout');
    }
}