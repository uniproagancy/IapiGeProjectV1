<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    //
    use SoftDeletes;

    protected $table = 'db_promotions';

    public function products()
    {
        return $this->hasMany(PromotionProduct::class, 'promotion_id', 'id')->orderBy('id', 'DESC');
    }

    public function translations()
    {
        return $this->hasMany(PromotionTranslation::class);
    }

    public function translation($locale = 'ka')
    {
        return $this->translations->where('locale', $locale)->first();
    }
}
