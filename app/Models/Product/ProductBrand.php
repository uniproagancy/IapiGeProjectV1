<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBrand extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_product_brands";

    protected $fillable = ['active', 'show'];

    public function translations()
    {
        return $this->hasMany(ProductBrandTranslation::class);
    }

    public function translation($locale = 'ka')
    {
        return $this->translations->where('locale', $locale)->first();
    }
}
