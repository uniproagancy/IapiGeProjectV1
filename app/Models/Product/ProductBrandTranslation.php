<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBrandTranslation extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_product_brand_translations";

    protected $fillable = [
        'product_brand_id',
        'locale',
        'title',
        'slug',
        'description',
        'keywords',
    ];
}
