<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSupplier extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_product_suppliers";

    public function translations()
    {
        return $this->hasMany(ProductSupplierTranslation::class);
    }

    public function translation($locale = 'ka')
    {
        return $this->translations->where('locale', $locale)->first();
    }
}
