<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductImage extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_product_images";

    protected $fillable = ['path', 'product_id'];
}
