<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionTranslation extends Model
{
    //
    use SoftDeletes;

    protected $table = 'db_promotion_translations';
}
