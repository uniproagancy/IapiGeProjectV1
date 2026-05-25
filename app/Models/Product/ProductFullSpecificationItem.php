<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductFullSpecificationItem extends Model
{
    //
    use SoftDeletes;

    protected $table = 'db_product_full_specification_items';

    protected $fillable = [
        'section_id',
        'name',
        'value',
        'filter',
    ];

    public function section()
    {
        return $this->belongsTo(ProductFullSpecificationSection::class, 'section_id');
    }
}
