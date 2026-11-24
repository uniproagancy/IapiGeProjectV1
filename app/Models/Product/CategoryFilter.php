<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * კატეგორიის ფილტრი: რომელი სპეციფიკაცია ჩანს ფილტრად, რა სახელით და რიგით.
 *
 * @property int         $category_id
 * @property string      $spec_name      სპეციფიკაციის სახელი, როგორც ბაზაშია („ტევადობა")
 * @property array|null  $aliases        იგივე მახასიათებელი სხვა სახელით („მოცულობა", „Capacity")
 * @property string|null $display_label  ვიტრინაზე საჩვენებელი სახელი
 * @property int         $sort_order
 * @property bool        $active
 */
class CategoryFilter extends Model
{
    protected $table = 'db_category_filters';

    protected $fillable = [
        'category_id',
        'spec_name',
        'aliases',
        'display_label',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'aliases'     => 'array',
        'sort_order'  => 'integer',
        'active'      => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}