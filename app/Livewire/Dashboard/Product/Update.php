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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Exception;

class Update extends Component
{
    use WithFileUploads;

    // ============================================
    // Properties
    // ============================================

    public $productId;
    public $category_id  = '';
    public $brand_id     = '';
    public $supplier_id  = '';
    public $sku          = '';
    public $quantity     = 0;
    public $active       = 1;
    public $in_stock     = 0;
    public $preorder     = 0;
    public $main_image;
    public $current_main_image = null;

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

    // ✅ დამატებითი სურათები
    public $additional_images  = [];
    public $existing_images    = [];

    // ============================================
    // Mount
    // ============================================

    public function mount(int $id): void
    {
        $product = Product::with([
            'translations',
            'price',
            'images',
        ])->findOrFail($id);

        $this->productId          = $product->id;
        $this->category_id        = $product->category_id;
        $this->brand_id           = $product->brand_id;
        $this->supplier_id        = $product->supplier_id;
        $this->sku                = $product->sku ?? '';
        $this->quantity           = $product->quantity ?? 0;
        $this->active             = $product->active;
        $this->in_stock           = $product->in_stock;
        $this->preorder           = $product->preorder;
        $this->current_main_image = $product->main_image;

        // ✅ ფასები
        if ($product->price) {
            $this->dealer_price   = $product->price->dealer_price ?? 0;
            $this->regular_price  = $product->price->regular_price ?? 0;
            $this->discount_price = $product->price->discount_price;
        }

        // ✅ თარგმანები
        foreach ($product->translations as $translation) {
            $locale = $translation->locale;
            $this->{"title_{$locale}"}       = $translation->title ?? '';
            $this->{"description_{$locale}"} = $translation->description ?? '';
            $this->{"keywords_{$locale}"}    = $translation->keywords ?? '';
        }

        // ✅ არსებული სურათები
        $this->existing_images = $product->images->map(fn ($img) => [
            'id'   => $img->id,
            'path' => $img->path,
        ])->toArray();
    }

    // ============================================
    // Validation
    // ============================================

    protected function rules(): array
    {
        return [
            'category_id'         => 'required|exists:db_product_categories,id',
            'brand_id'            => 'required|exists:db_product_brands,id',
            'supplier_id'         => 'required|exists:db_product_suppliers,id',
            'title_ka'            => 'required|string|max:255',
            'regular_price'       => 'required|numeric|min:0',
            'main_image'          => 'nullable|image|max:5120',
            'additional_images.*' => 'image|max:5120',
        ];
    }

    protected function messages(): array
    {
        return [
            'category_id.required'      => 'კატეგორია აუცილებელია',
            'brand_id.required'         => 'ბრენდი აუცილებელია',
            'supplier_id.required'      => 'მომწოდებელი აუცილებელია',
            'title_ka.required'         => 'დასახელება ქართულად აუცილებელია',
            'regular_price.required'    => 'ფასი აუცილებელია',
            'regular_price.numeric'     => 'ფასი უნდა იყოს რიცხვი',
            'regular_price.min'         => 'ფასი არ შეიძლება იყოს უარყოფითი',
            'main_image.image'          => 'სურათის ფორმატი არასწორია',
            'main_image.max'            => 'სურათი არ უნდა აღემატებოდეს 5MB-ს',
            'additional_images.*.image' => 'სურათის ფორმატი არასწორია',
            'additional_images.*.max'   => 'სურათი არ უნდა აღემატებოდეს 5MB-ს',
        ];
    }

    // ============================================
    // Delete existing image
    // ============================================

    public function deleteImage(int $imageId): void
    {
        try {
            $image = ProductImage::findOrFail($imageId);
            Storage::disk('public')->delete($image->path);
            $image->delete();

            $this->existing_images = array_filter(
                $this->existing_images,
                fn ($img) => $img['id'] !== $imageId
            );

            $this->dispatch('ui:success', message: 'სურათი წაიშალა');

        } catch (Exception $e) {
            Log::error('❌ Image delete error: ' . $e->getMessage());
            $this->dispatch('ui:error', message: 'სურათის წაშლა ვერ მოხერხდა');
        }
    }

    // ============================================
    // Save
    // ============================================

    public function save(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {

                $product = Product::findOrFail($this->productId);

                // ✅ პროდუქტის განახლება
                $product->update([
                    'category_id' => $this->category_id,
                    'brand_id'    => $this->brand_id,
                    'supplier_id' => $this->supplier_id,
                    'sku'         => $this->sku ?: null,
                    'quantity'    => (int) $this->quantity,
                    'active'      => (int) $this->active,
                    'in_stock'    => (int) $this->in_stock,
                    'preorder'    => (int) $this->preorder,
                ]);

                // ✅ ფასის განახლება
                ProductPrice::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'dealer_price'   => (float) ($this->dealer_price ?: 0),
                        'regular_price'  => (float) $this->regular_price,
                        'discount_price' => !empty($this->discount_price)
                            ? (float) $this->discount_price
                            : null,
                    ]
                );

                // ✅ თარგმანების განახლება
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

                    $existing = ProductTranslation::where('product_id', $product->id)
                        ->where('locale', $locale)
                        ->first();

                    $slug = $existing?->slug
                        ?? Str::slug($data['title']) . '-' . $product->id;

                    ProductTranslation::updateOrCreate(
                        ['product_id' => $product->id, 'locale' => $locale],
                        [
                            'title'       => $data['title'],
                            'slug'        => $slug,
                            'description' => $data['description'] ?: null,
                            'keywords'    => $data['keywords'] ?: null,
                        ]
                    );
                }

                // ✅ მთავარი სურათი — მხოლოდ ახალი ატვირთვის შემთხვევაში
                if ($this->main_image) {
                    if ($this->current_main_image) {
                        Storage::disk('public')->delete($this->current_main_image);
                    }

                    $path = $this->main_image->store(
                        'uploads/products/' . $product->id,
                        'public'
                    );

                    $product->update(['main_image' => $path]);
                    $this->current_main_image = $path;
                }

                // ✅ დამატებითი სურათები
                if (!empty($this->additional_images)) {
                    foreach ($this->additional_images as $image) {
                        $path = $image->store(
                            'uploads/products/' . $product->id,
                            'public'
                        );

                        $newImage = ProductImage::create([
                            'product_id' => $product->id,
                            'path'       => $path,
                        ]);

                        $this->existing_images[] = [
                            'id'   => $newImage->id,
                            'path' => $path,
                        ];
                    }

                    $this->reset('additional_images');
                }

                Log::info('✅ Product updated', ['product_id' => $product->id]);
            });

            $this->dispatch('ui:success', message: 'პროდუქტი წარმატებით განახლდა!');
            $this->redirect(route('dashboard.product.index'));

        } catch (Exception $e) {
            Log::error('❌ Product update error: ' . $e->getMessage(), [
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
        return view('livewire.dashboard.product.update', [
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