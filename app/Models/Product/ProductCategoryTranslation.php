<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategoryTranslation extends Model
{
    use HasFactory;

    protected $table = "db_product_category_translations";

    protected $fillable = [
        'product_category_id',
        'locale',
        'title',
        'slug',
        'description',
        'keywords'
    ];

    public $timestamps = false;

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
}
