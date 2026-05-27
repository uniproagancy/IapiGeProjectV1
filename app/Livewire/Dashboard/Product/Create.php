<?php

namespace App\Livewire\Dashboard\Product;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductFullSpecificationItem;
use App\Models\Product\ProductFullSpecificationSection;
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

    public $category_id  = '';
    public $brand_id     = '';
    public $supplier_id  = '';
    public $sku          = '';
    public $quantity     = 0;
    public $active       = 1;
    public $in_stock     = 0;
    public $preorder     = 0;
    public $main_image;

    public $dealer_price   = 0;
    public $regular_price  = 0;
    public $discount_price = null;

    public $title_ka       = '';
    public $title_en       = '';
    public $title_ru       = '';
    public $description_ka = '';
    public $description_en = '';
    public $description_ru = '';
    public $keywords_ka    = '';
    public $keywords_en    = '';
    public $keywords_ru    = '';

    public $additional_images = [];

    // ✅ სპეციფიკაციები — მასივი სექციებით
    public array $specSections    = [];
    public string $newSectionName = '';

    // ============================================
    // Spec methods — მხოლოდ state-ში, DB-ში save()-ზე
    // ============================================

    public function addSection(): void
    {
        $name = trim($this->newSectionName);
        if (empty($name)) return;

        $this->specSections[] = [
            'temp_id'  => uniqid(),
            'name'     => $name,
            'items'    => [],
            'newItem'  => ['name' => '', 'value' => '', 'filter' => 0],
        ];

        $this->newSectionName = '';
    }

    public function deleteSection(string $tempId): void
    {
        $this->specSections = array_values(
            array_filter($this->specSections, fn ($s) => $s['temp_id'] !== $tempId)
        );
    }

    public function addItem(string $tempId): void
    {
        foreach ($this->specSections as &$section) {
            if ($section['temp_id'] === $tempId) {
                $name  = trim($section['newItem']['name'] ?? '');
                $value = trim($section['newItem']['value'] ?? '');
                if (empty($name) || empty($value)) return;

                $section['items'][] = [
                    'item_id' => uniqid(),
                    'name'    => $name,
                    'value'   => $value,
                    'filter'  => (int) ($section['newItem']['filter'] ?? 0),
                ];
                $section['newItem'] = ['name' => '', 'value' => '', 'filter' => 0];
                break;
            }
        }
    }

    public function deleteItem(string $tempId, string $itemId): void
    {
        foreach ($this->specSections as &$section) {
            if ($section['temp_id'] === $tempId) {
                $section['items'] = array_values(
                    array_filter($section['items'], fn ($i) => $i['item_id'] !== $itemId)
                );
                break;
            }
        }
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
            'main_image'          => 'required|image|max:5120',
            'additional_images.*' => 'image|max:5120',
        ];
    }

    protected function messages(): array
    {
        return [
            'category_id.required'      => 'კატეგორია აუცილებელია',
            'category_id.exists'        => 'კატეგორია არასწორია',
            'brand_id.required'         => 'ბრენდი აუცილებელია',
            'brand_id.exists'           => 'ბრენდი არასწორია',
            'supplier_id.required'      => 'მომწოდებელი აუცილებელია',
            'supplier_id.exists'        => 'მომწოდებელი არასწორია',
            'title_ka.required'         => 'დასახელება ქართულად აუცილებელია',
            'regular_price.required'    => 'ფასი აუცილებელია',
            'regular_price.numeric'     => 'ფასი უნდა იყოს რიცხვი',
            'regular_price.min'         => 'ფასი არ შეიძლება იყოს უარყოფითი',
            'main_image.required'       => 'მთავარი სურათი აუცილებელია',
            'main_image.image'          => 'სურათის ფორმატი არასწორია',
            'main_image.max'            => 'სურათი არ უნდა აღემატებოდეს 5MB-ს',
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

                ProductPrice::create([
                    'product_id'     => $product->id,
                    'dealer_price'   => (float) ($this->dealer_price ?: 0),
                    'regular_price'  => (float) $this->regular_price,
                    'discount_price' => !empty($this->discount_price) ? (float) $this->discount_price : null,
                ]);

                $translations = [
                    'ka' => ['title' => $this->title_ka, 'description' => $this->description_ka, 'keywords' => $this->keywords_ka],
                    'en' => ['title' => $this->title_en, 'description' => $this->description_en, 'keywords' => $this->keywords_en],
                    'ru' => ['title' => $this->title_ru, 'description' => $this->description_ru, 'keywords' => $this->keywords_ru],
                ];

                foreach ($translations as $locale => $data) {
                    if (empty($data['title'])) continue;
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

                $mainPath = $this->main_image->store('uploads/products/' . $product->id, 'public');
                $product->update(['main_image' => $mainPath]);

                if (!empty($this->additional_images)) {
                    foreach ($this->additional_images as $image) {
                        $path = $image->store('uploads/products/' . $product->id, 'public');
                        ProductImage::create(['product_id' => $product->id, 'path' => $path]);
                    }
                }

                // ✅ სპეციფიკაციების შენახვა
                foreach ($this->specSections as $section) {
                    if (empty($section['name'])) continue;

                    $dbSection = ProductFullSpecificationSection::create([
                        'product_id' => $product->id,
                        'name'       => $section['name'],
                    ]);

                    foreach ($section['items'] as $item) {
                        if (empty($item['name']) || empty($item['value'])) continue;
                        ProductFullSpecificationItem::create([
                            'section_id' => $dbSection->id,
                            'name'       => $item['name'],
                            'value'      => $item['value'],
                            'filter'     => (int) ($item['filter'] ?? 0),
                        ]);
                    }
                }

                Log::info('✅ Product created', ['product_id' => $product->id]);
            });

            $this->dispatch('ui:success', message: 'პროდუქტი წარმატებით დაემატა!');
            $this->redirect(route('dashboard.product.index'));

        } catch (Exception $e) {
            Log::error('❌ Product create error: ' . $e->getMessage());
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
            'brands'     => ProductBrand::where('active', 1)->with('translations')->get(),
        ])->layout('livewire.dashboard.layout');
    }
}