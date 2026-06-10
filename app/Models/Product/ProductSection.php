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

    protected static function booted(): void
    {
        static::saving(function ($section) {
            if (empty($section->slug)) {
                $base = \Illuminate\Support\Str::slug($section->title);
                // ქართული title-ისთვის Str::slug ცარიელს აბრუნებs — fallback
                if ($base === '') {
                    $base = 'section';
                }
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->where('id', '!=', $section->id)->exists()) {
                    $slug = $base . '-' . (++$i);
                }
                $section->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

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