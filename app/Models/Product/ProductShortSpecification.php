<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductShortSpecification extends Model
{
    //
    use SoftDeletes;

    protected $table = 'db_product_short_specifications';

    protected $fillable = ['product_id', 'name', 'value'];

}
