<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class ProductVariationItem extends Model
{
    //
    protected $table = 'db_product_variation_items';

    protected $fillable = [
        'variation_id',
        'is_color',
        'value',
        'supplier_product_id',
    ];

    public function product()
    {
        return $this->hasOne(Product::class, 'supplier_product_id', 'supplier_product_id');
    }
}
