<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPrice extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_product_prices";

    protected $fillable = ['product_id', 'dealer_price', 'regular_price', 'discount_price', 'discount_percent'];
}
