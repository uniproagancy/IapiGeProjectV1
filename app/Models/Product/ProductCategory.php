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
        'google_category_id',
        'facebook_category_id',
        'alta_category_name',
        'zoommer_category_name',
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

    public function getActiveProducts(int $limit = 20)
    {
        $locale = app()->getLocale();

        $childCategoryIds = $this->children()->pluck('id');

        // თუ ქვეკატეგორიები არ აქვს — საკუთარი ID-ით ვეძებთ
        $categoryIds = $childCategoryIds->isNotEmpty()
            ? $childCategoryIds
            : collect([$this->id]);

        return Product::whereIn('category_id', $categoryIds)
            ->where('active', 1)
            ->where('show', 1)
            ->with([
                // მხოლოდ საჭირო locale — 3x ნაკლები მონაცემი
                'translations' => fn ($q) => $q
                    ->select('id', 'product_id', 'title', 'slug', 'locale')
                    ->where('locale', $locale),
                'price' => fn ($q) => $q
                    ->select('id', 'product_id', 'regular_price', 'discount_price', 'discount_percent'),
            ])
            ->select('id', 'category_id', 'brand_id', 'main_image', 'sku', 'show', 'active')
            ->orderByDesc('id')
            ->limit($limit)  // ← DB-ში LIMIT, არა PHP-ში
            ->get();
    }
}