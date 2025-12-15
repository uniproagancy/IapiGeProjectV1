<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionProduct extends Model
{
    //
    use SoftDeletes;

    protected $table = 'db_promotion_products';

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}
