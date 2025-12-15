<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_cities";

    public function translations()
    {
        return $this->hasMany(CityTranslation::class);
    }

    public function translation($locale = 'ka')
    {
        return $this->translations->where('locale', $locale)->first();
    }
}
