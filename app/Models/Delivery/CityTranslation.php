<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CityTranslation extends Model
{
    //
    use SoftDeletes;

    protected $table = 'db_city_translations';
}
