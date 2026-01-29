<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = "db_products";

    protected $fillable = [
        'category_id',
        'brand_id',
        'supplier_id',
        'sku',
        'supplier_product_id',
        'main_image',
        'quantity',
        'in_stock',
        'active',
        'show'
    ];

    public function translations()
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function translation($locale = 'ka')
    {
        return $this->translations->where('locale', $locale)->first();
    }

    public function brand()
    {
        return $this->hasOne(ProductBrand::class, 'id', 'brand_id');
    }

    public function category()
    {
        return $this->hasOne(ProductCategory::class, 'id', 'category_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function price()
    {
        return $this->hasOne(ProductPrice::class);
    }

    public function suppler()
    {
        return $this->hasOne(ProductSupplier::class);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class)->orderBy('name', 'DESC');
    }

    public function shortSpecifications()
    {
        return $this->hasMany(ProductShortSpecification::class);
    }

    public function fullSpecifications()
    {
        return $this->hasMany(ProductFullSpecificationSection::class);
    }

    public function specificationSections()
    {
        return $this->hasMany(ProductFullSpecificationSection::class, 'product_id');
    }
}