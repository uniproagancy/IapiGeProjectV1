<?php

namespace App\Livewire\Dashboard\Product;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductSupplier;
use App\Models\Product\ProductTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

    // ✅ დამატებითი სურათები — Livewire native
    public $additional_images = [];

    // ============================================
    // Validation
    // ============================================

    protected function rules(): array
    {
        return [
            'category_id'        => 'required|exists:db_product_categories,id',
            'brand_id'           => 'required|exists:db_product_brands,id',
            'supplier_id'        => 'required|exists:db_product_suppliers,id',
            'title_ka'           => 'required|string|max:255',
            'regular_price'      => 'required|numeric|min:0',
            'main_image'         => 'required|image|max:5120',
            'additional_images.*' => 'image|max:5120',
        ];
    }

    protected function messages(): array
    {
        return [
            'category_id.required'    => 'კატეგორია აუცილებელია',
            'category_id.exists'      => 'კატეგორია არასწორია',
            'brand_id.required'       => 'ბრენდი აუცილებელია',
            'brand_id.exists'         => 'ბრენდი არასწორია',
            'supplier_id.required'    => 'მომწოდებელი აუცილებელია',
            'supplier_id.exists'      => 'მომწოდებელი არასწორია',
            'title_ka.required'       => 'დასახელება ქართულად აუცილებელია',
            'regular_price.required'  => 'ფასი აუცილებელია',
            'regular_price.numeric'   => 'ფასი უნდა იყოს რიცხვი',
            'regular_price.min'       => 'ფასი არ შეიძლება იყოს უარყოფითი',
            'main_image.required'     => 'მთავარი სურათი აუცილებელია',
            'main_image.image'        => 'სურათის ფორმატი არასწორია',
            'main_image.max'          => 'სურათი არ უნდა აღემატებოდეს 5MB-ს',
            'additional_images.*.image' => 'სურათის ფორმატი არასწორია',
            'additional_images.*.max'   => 'სურათი არ უნდა აღემატებოდეს 5MB-ს',
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
                    'dealer_price'   => (float) ($this->dealer_price ?: 0),
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

                // ✅ მთავარი სურათის შენახვა
                $mainPath = $this->main_image->store(
                    'uploads/products/' . $product->id,
                    'public'
                );
                $product->update(['main_image' => $mainPath]);

                // ✅ დამატებითი სურათები — Livewire native upload
                if (!empty($this->additional_images)) {
                    foreach ($this->additional_images as $image) {
                        $path = $image->store(
                            'uploads/products/' . $product->id,
                            'public'
                        );

                        ProductImage::create([
                            'product_id' => $product->id,
                            'path'       => $path,
                        ]);
                    }
                }

                Log::info('✅ Product created', [
                    'product_id'        => $product->id,
                    'additional_images' => count($this->additional_images ?? []),
                ]);
            });

            $this->dispatch('ui:success', message: 'პროდუქტი წარმატებით დაემატა!');
            $this->redirect(route('dashboard.product.index'));

        } catch (Exception $e) {
            Log::error('❌ Product create error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('ui:error', message: 'შეცდომა მოხდა, სცადეთ ისევ');
        }
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
                ->with(['translations', 'children.translations'])
                ->get(),
            'brands'     => ProductBrand::where('active', 1)
                ->with('translations')
                ->get(),
        ])->layout('livewire.dashboard.layout');
    }
}