<?php

namespace App\Livewire\Dashboard\Product;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductSupplier;
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

    public $alneo_file;
    public $midea_file;
    public $kontakt_file;
    public $comfoFile        = null;
    public $elite_file;
    public $metromart_file;
    public $ingco_file;

    public $selectedCategory    = null;
    public $selectedBrand       = null;
    public $selectedSubcategory = null;
    public $subcategories       = [];

    public $category_id  = null;
    public $brand_id     = null;
    public $supplier_id  = null;
    public $price_min    = null;
    public $price_max    = null;

    public $priceEditProductId     = null;
    public $priceEditDealerPrice   = 0;
    public $priceEditRegularPrice  = 0;
    public $priceEditDiscountPrice = null;

    // Quick Edit
    public $quickEditProductId     = null;
    public $quickEditCategoryId    = null;
    public $quickEditSubcategoryId = null;
    public $quickEditBrandId       = null;
    public $quickEditRegularPrice  = 0;
    public $quickEditDiscountPrice = null;
    public array $quickEditSubcategories = [];

    // Bulk actions
    public $bulkCategoryId    = null;
    public $bulkSubcategoryId = null;
    public $bulkBrandId       = null;
    public array $bulkSubcategories = [];

    public bool $only_locked = false;
    public bool $no_brand    = false;

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
        'price_min'     => ['except' => ''],
        'price_max'     => ['except' => ''],
    ];

    public function mount(): void {}

    public function paginationView(): string
    {
        return 'livewire.dashboard.partials._pagination';
    }

    // ============================================
    // Toggle
    // ============================================

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

    public function toggleLock($productId): void
    {
        $product = Product::findOrFail($productId);
        $product->update_lock = !$product->update_lock;
        $product->save();
        $this->dispatch('ui:success', message: $product->update_lock
            ? 'პროდუქტი ჩაიკეტა (განახლება გათიშულია)'
            : 'პროდუქტი განიბლოკა');
    }

    // ============================================
    // Delete / Restore
    // ============================================

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

    // ============================================
    // Filters
    // ============================================

    public function applyFilters(): void
    {
        $this->resetPage();
        $this->dispatch('filter_modal_close');
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search_query', 'no_brand', 'order_dir', 'per_page',
            'with_trashed', 'show_web', 'status_active', 'unsorted',
            'supplier_id', 'price_min', 'price_max',
        ]);
        $this->resetPage();
        $this->dispatch('filter_modal_close');
    }

    // ============================================
    // Select
    // ============================================

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

    // ============================================
    // Category / Brand update
    // ============================================

    public function updateProductCategory(): void
    {
        $this->validate(['selectedCategory' => 'required|exists:db_product_categories,id']);

        $categoryId = $this->selectedSubcategory ?? $this->selectedCategory;
        Product::whereIn('id', $this->selectedProducts)->update(['category_id' => $categoryId]);
        $this->selectedProducts = [];
        $this->dispatch('category_modal_close');
        $this->dispatch('ui:success', message: 'კატეგორია განახლდა!');
    }

    public function updateProductBrand(): void
    {
        $this->validate(['selectedBrand' => 'required|exists:db_product_brands,id']);

        Product::whereIn('id', $this->selectedProducts)->update(['brand_id' => $this->selectedBrand]);
        $this->selectedProducts = [];
        $this->dispatch('brand_modal_close');
        $this->dispatch('ui:success', message: 'ბრენდი განახლდა!');
    }

    // ============================================
    // Price Edit
    // ============================================

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
    // Quick Edit
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
    // Bulk actions
    // ============================================

    public function updatedBulkCategoryId($value): void
    {
        $this->bulkSubcategories = ProductCategory::where('parent_id', $value)->get()->toArray();
        $this->bulkSubcategoryId = null;
    }

    public function bulkUpdateCategory(): void
    {
        if (empty($this->selectedProducts)) { $this->dispatch('ui:error', message: 'პროდუქტი არ არის არჩეული!'); return; }
        $finalCategoryId = $this->bulkSubcategoryId ?? $this->bulkCategoryId;
        if (empty($finalCategoryId)) { $this->dispatch('ui:error', message: 'კატეგორია აუცილებელია!'); return; }
        Product::whereIn('id', $this->selectedProducts)->update(['category_id' => $finalCategoryId]);
        $this->selectedProducts = []; $this->selectAll = false;
        $this->dispatch('bulk_modal_close');
        $this->dispatch('ui:success', message: 'კატეგორია განახლდა!');
    }

    public function bulkUpdateBrand(): void
    {
        if (empty($this->selectedProducts)) { $this->dispatch('ui:error', message: 'პროდუქტი არ არის არჩეული!'); return; }
        if (empty($this->bulkBrandId)) { $this->dispatch('ui:error', message: 'ბრენდი აუცილებელია!'); return; }
        Product::whereIn('id', $this->selectedProducts)->update(['brand_id' => $this->bulkBrandId]);
        $this->selectedProducts = []; $this->selectAll = false;
        $this->dispatch('bulk_modal_close');
        $this->dispatch('ui:success', message: 'ბრენდი განახლდა!');
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedProducts)) { $this->dispatch('ui:error', message: 'პროდუქტი არ არის არჩეული!'); return; }
        $count = count($this->selectedProducts);
        Product::whereIn('id', $this->selectedProducts)->update(['active' => 0]);
        Product::whereIn('id', $this->selectedProducts)->delete();
        $this->selectedProducts = []; $this->selectAll = false;
        $this->dispatch('bulk_modal_close');
        $this->dispatch('ui:success', message: "{$count} პროდუქტი წაიშალა!");
    }

    public function bulkRestore(): void
    {
        if (empty($this->selectedProducts)) { $this->dispatch('ui:error', message: 'პროდუქტი არ არის არჩეული!'); return; }
        $count = count($this->selectedProducts);
        Product::withTrashed()->whereIn('id', $this->selectedProducts)->restore();
        $this->selectedProducts = []; $this->selectAll = false;
        $this->dispatch('bulk_modal_close');
        $this->dispatch('ui:success', message: "{$count} პროდუქტი აღდგა!");
    }

    public function bulkLock(): void
    {
        if (empty($this->selectedProducts)) { $this->dispatch('ui:error', message: 'პროდუქტი არ არის არჩეული!'); return; }
        $count = count($this->selectedProducts);
        Product::whereIn('id', $this->selectedProducts)->update(['update_lock' => 1]);
        $this->selectedProducts = []; $this->selectAll = false;
        $this->dispatch('bulk_modal_close');
        $this->dispatch('ui:success', message: "{$count} პროდუქტი ჩაიკეტა!");
    }

    public function bulkUnlock(): void
    {
        if (empty($this->selectedProducts)) { $this->dispatch('ui:error', message: 'პროდუქტი არ არის არჩეული!'); return; }
        $count = count($this->selectedProducts);
        Product::whereIn('id', $this->selectedProducts)->update(['update_lock' => 0]);
        $this->selectedProducts = []; $this->selectAll = false;
        $this->dispatch('bulk_modal_close');
        $this->dispatch('ui:success', message: "{$count} პროდუქტი განიბლოკა!");
    }

    // ============================================
    // Export
    // ============================================

    public function exportNotFound(): StreamedResponse
    {
        $rows     = \App\Models\Product\GlobalNotFound::orderBy('name')->get();
        $filename = 'not_found_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Name', 'Stock', 'Price', 'Reason', 'Date']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->name, $r->stock, $r->price, $r->reason, $r->created_at]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ============================================
    // Helpers
    // ============================================

    private function parsePrice($value): ?float
    {
        if ($value === null || $value === '') return null;
        $str = trim((string) $value);
        $str = preg_replace('/[^0-9,.]/', '', $str);
        if ($str === '') return null;

        if (preg_match('/,(\d{2})$/', $str)) {
            $str = str_replace(',', '.', $str);
        } else {
            $str = str_replace(',', '', $str);
        }

        return (float) $str ?: null;
    }

    private function parseAlneoPrice($value): float
    {
        if ($value === null || $value === '') return 0.0;
        $str = trim((string) $value);
        $str = preg_replace('/[^0-9,.]/', '', $str);
        if ($str === '') return 0.0;

        if (preg_match('/,(\d{2})$/', $str)) {
            $str = str_replace(',', '.', $str);
        } else {
            $str = str_replace(',', '', $str);
        }

        return (float) $str;
    }

    private function parseStock($value): int
    {
        if ($value === null || $value === '') return 0;
        $digits = preg_replace('/[^0-9]/', '', (string) $value);
        return $digits === '' ? 0 : (int) $digits;
    }

    // ============================================
    // Alneo
    // ============================================

    public function uploadAlneo(): void
    {
        $this->validate([
            'alneo_file' => 'required|file|mimes:xlsx,xls|max:20480',
        ], [
            'alneo_file.required' => 'ფაილი აუცილებელია',
            'alneo_file.mimes'    => 'მხოლოდ Excel ფაილი (.xlsx/.xls)',
        ]);

        try {
            @ini_set('memory_limit', '256M');

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->alneo_file->getRealPath());
            $sheet       = $spreadsheet->getActiveSheet();

            $inserted = 0;
            $updated  = 0;
            $skipped  = 0;

            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex = $row->getRowIndex();

                $skuCell = $sheet->getCell('A' . $rowIndex);
                $skuCell->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
                $sku = trim((string) $skuCell->getFormattedValue());

                if ($sku === '' || in_array(mb_strtolower($sku), ['sku', 'კოდი', 'id', 'code'])) {
                    continue;
                }

                $stock         = (int) preg_replace('/[^0-9]/', '', (string) $sheet->getCell('B' . $rowIndex)->getValue());
                $priceRaw      = $sheet->getCell('C' . $rowIndex)->getFormattedValue();
                $discountRaw   = $sheet->getCell('D' . $rowIndex)->getFormattedValue();

                $price         = $this->parseAlneoPrice($priceRaw);
                $discountPrice = $this->parseAlneoPrice($discountRaw);

                $exists = \App\Models\AlneoProduct::where('sku', $sku)->first();

                if ($exists) {
                    $exists->update([
                        'stock'          => $stock,
                        'price'          => $price,
                        'discount_price' => $discountPrice ?: null,
                    ]);
                    $updated++;
                } else {
                    \App\Models\AlneoProduct::create([
                        'sku'            => $sku,
                        'stock'          => $stock,
                        'price'          => $price,
                        'discount_price' => $discountPrice ?: null,
                    ]);
                    $inserted++;
                }
            }

            $this->reset('alneo_file');
            $this->dispatch('uploadAlneoModal_close');
            $this->dispatch('ui:success', message: "Alneo: {$inserted} ახალი, {$updated} განახლდა, {$skipped} გამოტოვებული.");

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Alneo upload error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეცდომა: ' . $e->getMessage());
        }
    }

    public function uploadAlneoScan(): void
    {
        try {
            \App\Jobs\AlneoScanJob::dispatch()->onQueue('alneo');
            $this->dispatch('ui:success', message: 'Alneo სკანი დაიწყო!');
        } catch (\Throwable $e) {
            Log::error('Alneo Scan dispatch failed', ['error' => $e->getMessage()]);
            $this->dispatch('ui:error', message: 'შეცდომა Alneo სკანის გაშვებისას');
        }
    }

    // ============================================
    // Ingco
    // ============================================

    public function uploadIngco(): void
    {
        $this->validate([
            'ingco_file' => 'required|file|mimes:xlsx,xls|max:20480',
        ], [
            'ingco_file.required' => 'ფაილი აუცილებელია',
            'ingco_file.mimes'    => 'მხოლოდ Excel ფაილი (.xlsx/.xls)',
        ]);

        try {
            @ini_set('memory_limit', '256M');

            $spreadsheet = IOFactory::load($this->ingco_file->getRealPath());
            $sheet       = $spreadsheet->getActiveSheet();

            $inserted = 0;
            $updated  = 0;
            $skipped  = 0;

            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex = $row->getRowIndex();

                // A = მოდელი (SKU)
                $skuCell = $sheet->getCell('A' . $rowIndex);
                $skuCell->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
                $sku = trim((string) $skuCell->getFormattedValue());

                if ($sku === '' || in_array(mb_strtolower($sku), ['მოდელი', 'model', 'sku', 'კოდი', 'id'])) {
                    $skipped++;
                    continue;
                }

                // B = ფასი, C = სააქციო ფასი
                $priceRaw    = $sheet->getCell('B' . $rowIndex)->getFormattedValue();
                $discountRaw = $sheet->getCell('C' . $rowIndex)->getFormattedValue();

                $price         = $this->parseAlneoPrice($priceRaw);
                $discountPrice = $this->parseAlneoPrice($discountRaw) ?: null;

                if ($price <= 0) {
                    $skipped++;
                    continue;
                }

                $exists = \App\Models\IngcoProduct::where('sku', $sku)->first();

                if ($exists) {
                    $exists->update([
                        'price'          => $price,
                        'discount_price' => $discountPrice,
                    ]);
                    $updated++;
                } else {
                    \App\Models\IngcoProduct::create([
                        'sku'            => $sku,
                        'stock'          => 1,
                        'price'          => $price,
                        'discount_price' => $discountPrice,
                    ]);
                    $inserted++;
                }
            }

            $this->reset('ingco_file');
            $this->dispatch('uploadIngcoModal_close');
            $this->dispatch('ui:success', message: "Ingco: {$inserted} ახალი, {$updated} განახლდა, {$skipped} გამოტოვებული.");

        } catch (\Throwable $e) {
            Log::error('Ingco upload error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეცდომა: ' . $e->getMessage());
        }
    }

    // ============================================
    // Elite
    // ============================================

    public function uploadElite(): void
    {
        $this->validate([
            'elite_file' => 'required|file|mimes:xlsx,xls|max:20480',
        ]);

        try {
            @ini_set('memory_limit', '256M');

            $spreadsheet = IOFactory::load($this->elite_file->getRealPath());
            $sheet       = $spreadsheet->getActiveSheet();

            $inserted  = 0;
            $duplicate = 0;
            $skipped   = 0;

            foreach ($sheet->getRowIterator() as $row) {
                $barCode = trim((string) $sheet->getCell('A' . $row->getRowIndex())->getValue());

                if (!$barCode) { $skipped++; continue; }

                if (\App\Models\EliteProduct::where('bar_code', $barCode)->exists()) {
                    $duplicate++; continue;
                }

                \App\Models\EliteProduct::create(['bar_code' => $barCode, 'synced' => false]);
                $inserted++;
            }

            $this->reset('elite_file');
            $this->dispatch('uploadEliteModal_close');
            $this->dispatch('ui:success', message: "Elite: {$inserted} ჩაიწერა, {$duplicate} დუბლიკატი, {$skipped} გამოტოვებული.");

        } catch (\Throwable $e) {
            Log::error('Elite Upload error', ['error' => $e->getMessage()]);
            $this->dispatch('ui:error', message: 'შეცდომა: ' . $e->getMessage());
        }
    }

    // ============================================
    // Metromart
    // ============================================

    public function uploadMetromart(): void
    {
        $this->validate([
            'metromart_file' => 'required|file|mimes:xlsx,xls|max:20480',
        ], [
            'metromart_file.required' => 'ფაილი აუცილებელია',
            'metromart_file.mimes'    => 'მხოლოდ Excel ფაილი',
        ]);

        try {
            @ini_set('memory_limit', '256M');

            $spreadsheet = IOFactory::load($this->metromart_file->getRealPath());
            $sheet       = $spreadsheet->getActiveSheet();

            $dispatched = 0;
            $skipped    = 0;

            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex = $row->getRowIndex();
                $model    = trim((string) $sheet->getCell('A' . $rowIndex)->getValue());

                if (!$model) { $skipped++; continue; }
                if (in_array(mb_strtolower($model), ['მოდელი', 'model', 'sku', 'id'])) { $skipped++; continue; }

                // B სვეტი — ფასი (სურვილისამებრ)
                $price = $this->parseAlneoPrice($sheet->getCell('B' . $rowIndex)->getFormattedValue());

                $stockRaw = trim((string) $sheet->getCell('C' . $rowIndex)->getValue());
                $stock    = $stockRaw === '' ? 1 : (int) preg_replace('/[^0-9]/', '', $stockRaw);
                
                \App\Jobs\MetromartProductJob::dispatch($model, $price, $stock)->onQueue('metromart');
                $dispatched++;
            }

            Log::info("📂 Metromart upload: {$dispatched} job queue-ში, გამოტოვებული: {$skipped}");

            $this->reset('metromart_file');
            $this->dispatch('ui:success', message: "{$dispatched} მოდელი queue-ში გაიგზავნა.");

        } catch (\Throwable $e) {
            Log::error('Metromart upload error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეცდომა: ' . $e->getMessage());
        }
    }

    // ============================================
    // Midea
    // ============================================

    public function uploadMidea(): void
    {
        $this->validate([
            'midea_file' => 'required|file|mimes:xlsx,xls,csv',
        ], [
            'midea_file.required' => 'ფაილი აუცილებელია',
            'midea_file.mimes'    => 'მხოლოდ Excel ან CSV ფაილი',
        ]);

        try {
            $rows  = IOFactory::load($this->midea_file->getRealPath())->getActiveSheet()->toArray(null, true, true, false);
            $items = [];

            foreach ($rows as $row) {
                $name = trim((string) ($row[0] ?? ''));
                if ($name === '') continue;
                if (in_array(mb_strtolower($name), ['item', 'items', 'model', 'დასახელება', 'name', 'სახელი', 'პროდუქტი'])) continue;
                if (mb_strlen($name) < 3) continue;

                $name = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', ' ', $name);
                $name = preg_replace('/\s+/u', ' ', $name);
                $name = trim($name);

                $items[$name] = [
                    'stock' => $this->parseStock($row[1] ?? null),
                ];
            }

            if (empty($items)) { $this->dispatch('ui:error', message: 'დასახელებები ვერ მოიძებნა'); return; }

            foreach ($items as $name => $info) {
                \App\Jobs\MideaProductJob::dispatch($name, $info['stock'])->onQueue('midea');
            }

            $this->reset('midea_file');
            $this->dispatch('ui:success', message: count($items) . ' პროდუქტი queue-ში გაიგზავნა.');

        } catch (\Throwable $e) {
            Log::error('Midea upload error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეცდომა: ' . $e->getMessage());
        }
    }

    // ============================================
    // Kontakt
    // ============================================

    public function uploadKontakt(): void
    {
        $this->validate([
            'kontakt_file' => 'required|file|mimes:xlsx,xls',
        ], [
            'kontakt_file.required' => 'ფაილი აუცილებელია',
            'kontakt_file.mimes'    => 'მხოლოდ Excel ფაილი (.xlsx/.xls)',
        ]);

        try {
            $sheet      = IOFactory::load($this->kontakt_file->getRealPath())->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $rows       = [];
            $skipped    = 0;

            for ($r = 2; $r <= $highestRow; $r++) {
                $cell = $sheet->getCell('A' . $r);
                $name = trim((string) $cell->getValue());
                $name = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', ' ', $name);
                $name = preg_replace('/\s+/u', ' ', $name);
                $name = trim($name);

                if ($name === '') continue;
                if (in_array(mb_strtolower($name), ['item', 'items', 'model', 'დასახელება', 'name', 'სახელი', 'პროდუქტი'])) continue;
                if (mb_strlen($name) < 2) continue;

                $url = '';
                if ($cell->hasHyperlink()) {
                    $url = trim($cell->getHyperlink()->getUrl());
                }

                if ($url === '' || !str_starts_with($url, 'http')) { $skipped++; continue; }

                $rows[] = [
                    'model' => $name,
                    'url' => $url,
                    'stock' => $this->parseStock($sheet->getCell('B' . $r)->getValue()),
                    'price' => $this->parsePrice($sheet->getCell('C' . $r)->getValue()),
                    'discount_price' => $this->parsePrice($sheet->getCell('D' . $r)->getValue()),
                ];
            }

            if (empty($rows)) { $this->dispatch('ui:error', message: 'ვერცერთი ვალიდური ლინკი ვერ მოიძებნა'); return; }

            \App\Jobs\KontaktBulkImportJob::dispatch($rows)->onQueue('kontakt');

            $this->reset('kontakt_file');
            $this->dispatch('ui:success', message: count($rows) . " პროდუქტი გაიგზავნა ერთ bulk job-ში (გამოტოვებული: {$skipped}).");

        } catch (\Throwable $e) {
            Log::error('Kontakt upload error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეცდომა: ' . $e->getMessage());
        }
    }

    // ============================================
    // Comfo
    // ============================================

    public function uploadComfo(): void
    {
        $this->validate(['comfoFile' => 'required|file|mimes:xlsx,xls|max:10240']);

        try {
            $sheet      = IOFactory::load($this->comfoFile->getRealPath())->getActiveSheet();
            $rows       = $sheet->getHighestRow();
            $dispatched = 0;
            $skipped    = 0;

            for ($r = 2; $r <= $rows; $r++) {
                $id    = trim((string) $sheet->getCell("A{$r}")->getValue());
                $stock = $sheet->getCell("B{$r}")->getValue();
                $url   = trim((string) $sheet->getCell("C{$r}")->getValue());

                if (empty($id) || empty($url) || !str_starts_with($url, 'http')) { $skipped++; continue; }

                $stockInt = is_numeric($stock) ? (int) $stock : (int) preg_replace('/\D+/', '', (string) $stock);

                \App\Jobs\ComfoImportJob::dispatch($id, $stockInt, $url)->onQueue('comfo');
                $dispatched++;
            }

            $this->dispatch('uploadComfoModal_close');
            $this->dispatch('ui:success', message: "Comfo: დაიგზავნა {$dispatched}, გამოტოვებული {$skipped}");
            $this->reset('comfoFile');

        } catch (\Throwable $e) {
            $this->dispatch('ui:error', message: 'Comfo Excel შეცდომა: ' . $e->getMessage());
        }
    }

    // ============================================
    // Render
    // ============================================

    public function render()
    {
        $query = Product::with(['translations'])
            ->when($this->search_query, fn($q) => $q->where(function($sub) {
                $sub->whereHas('translations',
                    fn($t) => $t->where('title', 'like', "%{$this->search_query}%")
                )->orWhere('sku', 'like', "%{$this->search_query}%");
            }))
            ->when($this->show_web === true,      fn($q) => $q->where('show', $this->show_web))
            ->when($this->category_id,            fn($q) => $q->where('category_id', $this->category_id))
            ->when($this->brand_id,               fn($q) => $q->where('brand_id', $this->brand_id))
            ->when($this->supplier_id,            fn($q) => $q->where('supplier_id', $this->supplier_id))
            ->when($this->status_active === true, fn($q) => $q->where('active', $this->status_active))
            ->when($this->unsorted === true,      fn($q) => $q->whereIn('category_id', [3, 4, 182, 203, 204, 205, 210]))
            ->when($this->no_brand === true,      fn($q) => $q->whereIn('brand_id', [1, 6]))
            ->when($this->only_locked === true,   fn($q) => $q->where('update_lock', 1))
            ->when($this->price_min,              fn($q) => $q->whereHas('price',
                fn($p) => $p->whereRaw('COALESCE(NULLIF(discount_price,0), regular_price) >= ?', [(float) $this->price_min])
            ))
            ->when($this->price_max,              fn($q) => $q->whereHas('price',
                fn($p) => $p->whereRaw('COALESCE(NULLIF(discount_price,0), regular_price) <= ?', [(float) $this->price_max])
            ))
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