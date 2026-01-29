<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Model
{
    use SoftDeletes;

    protected $table = "db_product_variations";

    protected $fillable = [
        'product_id',
        'name',
        'value',
    ];

    public function items()
    {
        return $this->hasMany(ProductVariationItem::class, 'variation_id');
    }
}