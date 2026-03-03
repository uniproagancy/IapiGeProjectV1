<?php

namespace App\Livewire\Dashboard\Product;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductSupplier;
use App\Models\Product\ProductTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Exception;

class Create extends Component
{
    use WithFileUploads;

    // ============================================
    // Properties
    // ============================================

    public $category_id  = '';
    public $brand_id     = '';
    public $supplier_id  = '';
    public $sku          = '';
    public $quantity     = 0;
    public $active       = 1;
    public $in_stock     = 0;
    public $preorder     = 0;
    public $main_image;

    // ✅ ფასები
    public $dealer_price   = 0;
    public $regular_price  = 0;
    public $discount_price = null;

    // ✅ თარგმანები
    public $title_ka       = '';
    public $title_en       = '';
    public $title_ru       = '';
    public $description_ka = '';
    public $description_en = '';
    public $description_ru = '';
    public $keywords_ka    = '';
    public $keywords_en    = '';
    public $keywords_ru    = '';

    // ============================================
    // Validation
    // ============================================

    protected function rules(): array
    {
        return [
            'category_id'   => 'required|exists:db_product_categories,id',
            'brand_id'      => 'required|exists:db_product_brands,id',
            'supplier_id'   => 'required|exists:db_product_suppliers,id',
            'title_ka'      => 'required|string|max:255',
            'regular_price' => 'required|numeric|min:0',
            'main_image'    => 'required|image|max:5120',
        ];
    }

    protected function messages(): array
    {
        return [
            'category_id.required'   => 'კატეგორია აუცილებელია',
            'brand_id.required'      => 'ბრენდი აუცილებელია',
            'supplier_id.required'   => 'მომწოდებელი აუცილებელია',
            'title_ka.required'      => 'დასახელება ქართულად აუცილებელია',
            'regular_price.required' => 'ფასი აუცილებელია',
            'main_image.required'    => 'მთავარი სურათი აუცილებელია',
            'main_image.image'       => 'სურათის ფორმატი არასწორია',
            'main_image.max'         => 'სურათი არ უნდა აღემატებოდეს 5MB-ს',
        ];
    }

    // ============================================
    // Save
    // ============================================

    public function save(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {

                // ✅ პროდუქტის შექმნა
                $product = Product::create([
                    'category_id' => $this->category_id,
                    'brand_id'    => $this->brand_id,
                    'supplier_id' => $this->supplier_id,
                    'sku'         => $this->sku ?: null,
                    'quantity'    => (int) $this->quantity,
                    'active'      => (int) $this->active,
                    'in_stock'    => (int) $this->in_stock,
                    'preorder'    => (int) $this->preorder,
                    'main_image'  => null,
                    'show'        => 1,
                ]);

                // ✅ ფასის შექმნა
                ProductPrice::create([
                    'product_id'     => $product->id,
                    'dealer_price'   => (float) $this->dealer_price ?: 0,
                    'regular_price'  => (float) $this->regular_price,
                    'discount_price' => !empty($this->discount_price)
                        ? (float) $this->discount_price
                        : null,
                ]);

                // ✅ თარგმანების შექმნა
                $translations = [
                    'ka' => [
                        'title'       => $this->title_ka,
                        'description' => $this->description_ka,
                        'keywords'    => $this->keywords_ka,
                    ],
                    'en' => [
                        'title'       => $this->title_en,
                        'description' => $this->description_en,
                        'keywords'    => $this->keywords_en,
                    ],
                    'ru' => [
                        'title'       => $this->title_ru,
                        'description' => $this->description_ru,
                        'keywords'    => $this->keywords_ru,
                    ],
                ];

                foreach ($translations as $locale => $data) {
                    if (empty($data['title'])) {
                        continue;
                    }

                    $slug = Str::slug($data['title']) . '-' . $product->id;

                    ProductTranslation::create([
                        'product_id'  => $product->id,
                        'locale'      => $locale,
                        'title'       => $data['title'],
                        'slug'        => $slug,
                        'description' => $data['description'] ?: null,
                        'keywords'    => $data['keywords'] ?: null,
                    ]);
                }

                // ✅ სურათის შენახვა
                $path = $this->main_image->store(
                    'uploads/products/' . $product->id,
                    'public'
                );

                $product->update(['main_image' => $path]);

                Log::info('✅ Product created', ['product_id' => $product->id]);
            });

            $this->dispatch('ui:success', message: 'პროდუქტი წარმატებით დაემატა!');
            $this->resetForm();

        } catch (Exception $e) {
            Log::error('❌ Product create error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეცდომა მოხდა, სცადეთ ისევ');
        }
    }

    // ============================================
    // Reset
    // ============================================

    private function resetForm(): void
    {
        $this->reset([
            'category_id', 'brand_id', 'supplier_id',
            'sku', 'quantity', 'in_stock', 'preorder',
            'dealer_price', 'regular_price', 'discount_price',
            'title_ka', 'title_en', 'title_ru',
            'description_ka', 'description_en', 'description_ru',
            'keywords_ka', 'keywords_en', 'keywords_ru',
            'main_image',
        ]);
        $this->active = 1;
    }

    // ============================================
    // Render
    // ============================================

    public function render()
    {
        return view('livewire.dashboard.product.create', [
            'suppliers'  => ProductSupplier::where('active', 1)->get(),
            'categories' => ProductCategory::where('parent_id', 0)
                ->where('active', '!=', 0)
                ->where('id', '!=', 1)
                ->with('children.translations')
                ->get(),
            'brands'     => ProductBrand::where('active', 1)->get(),
        ])->layout('livewire.dashboard.layout');
    }
}