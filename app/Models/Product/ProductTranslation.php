<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class ProductTranslation extends Model
{
    //
    protected $table = "db_product_translations";

    protected $fillable = [
        'product_id',
        'locale',
        'title',
        'slug',
        'description',
        'keywords'
    ];
}
