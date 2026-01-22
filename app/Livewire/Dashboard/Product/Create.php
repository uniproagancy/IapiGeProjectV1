<?php

namespace App\Livewire\Dashboard\Product;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductSupplier;

use App\Models\ProductTranslation;
use Illuminate\Support\Str;

use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{

    public $category_id;
    public $brand_id;
    public $supplier_id;
    public $sku;
    public $quantity;
    public $active;
    public $in_stock;
    public $preorder;
    public $main_image;

    public $title_ka, $title_en, $title_ru;
    public $description_ka, $description_en, $description_ru;
    public $keywords_ka, $keywords_en, $keywords_ru;

    use WithFileUploads;
    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:db_product_categories,id|not_in:0',
            'brand_id' => 'required|exists:db_product_brands,id|not_in:0',
            'supplier_id' => 'required|exists:db_product_suppliers,id|not_in:0',
            'main_image' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'გთხოვთ შეავსოთ ყველა აუცილებელი ველი!',
        ];
    }

    public function save()
    {
        $this->validate();
        $product = Product::create([
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'supplier_id' => $this->supplier_id,
            'sku' => $this->sku,
            'quantity' => $this->quantity ?? 0,
            'active' => $this->active ?? 0,
            'in_stock' => $this->in_stock ?? 0,
            'preorder' => $this->preorder ?? 0,
        ]);
        $translations = [
            ['locale' => 'ka', 'title' => $this->title_ka, 'description' => $this->description_ka, 'keywords' => 0],
            ['locale' => 'en', 'title' => $this->title_en, 'description' => $this->description_en, 'keywords' => 0],
            ['locale' => 'ru', 'title' => $this->title_ru, 'description' => $this->description_ru, 'keywords' => 0],
        ];
        foreach ($translations as $t) {
            if (!empty($t['title'])) {
                $baseSlug = Str::slug($t['title'], '-');
                $slugWithId = "{$baseSlug}-{$product->id}";
                ProductTranslation::create([
                    'product_id' => $product->id,
                    'locale' => $t['locale'],
                    'title' => $t['title'],
                    'slug' => $slugWithId,
                    'description' => $t['description'] ?? null,
                    'keywords' => $t['keywords'] ?? null,
                ]);
            }
        }
        $main_image = $this->main_image;
        $path = $main_image->store('uploads/products/'.$product->id, 'public');
        Product::find($product->id)->update([
            'main_image' => $path,
        ]);
    }

    public function render()
    {
        return view('livewire.dashboard.product.create', [
            'suppliers' => ProductSupplier::where('active', 1)->get(),
            'categories' => ProductCategory::where('parent_id', 0)->where('active', '!=', 0)->where('id', '!=', 1   )->get(),
            'brands' => ProductBrand::where('active', 1)->get(),
        ])->layout('livewire.dashboard.layout');
    }
}
