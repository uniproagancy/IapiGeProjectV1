<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSection extends Model
{
    protected $table = 'db_product_sections';

    protected $fillable = [
        'title', 'image', 'category_id', 'show_on_home', 'sort_order', 'active',
    ];

    protected $casts = [
        'show_on_home' => 'boolean',
        'active'       => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'db_product_section_items',
            'section_id',
            'product_id'
        )->withPivot('sort_order')->withTimestamps()->orderBy('db_product_section_items.sort_order');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}