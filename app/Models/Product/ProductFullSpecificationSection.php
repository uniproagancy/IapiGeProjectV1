<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductFullSpecificationSection extends Model
{
    //
    use SoftDeletes;

    protected $table = 'db_product_full_specification_sections';

    protected $fillable = ['name', 'product_id'];

    public function list()
    {
        return $this->hasMany(ProductFullSpecificationItem::class, 'section_id', 'id');
    }

    public function filter()
    {
        return $this->hasMany(ProductFullSpecificationItem::class, 'section_id', 'id')->where('filter', 1);
    }

}
