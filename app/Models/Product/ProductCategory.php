<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "db_product_categories";

    protected $fillable = [
        'parent_id',
        'active',
        'show',
        'show_on_main',
    ];

    public function translations()
    {
        return $this->hasMany(ProductCategoryTranslation::class);
    }

    public function translation($locale = 'ka')
    {
        return $this->translations->where('locale', $locale)->first();
    }

    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function getActiveProducts()
    {
        $childCategoryIds = $this->children()->pluck('id');

        return Product::whereIn('category_id', $childCategoryIds)
            ->where('active', 1)
            ->where('show', 1)
            ->with(['translations', 'price'])
            ->get();
    }


}
