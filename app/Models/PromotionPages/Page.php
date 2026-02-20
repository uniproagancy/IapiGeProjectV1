<?php

namespace App\Models\PromotionPages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    //
    use SoftDeletes;

    protected $table = 'db_promotion_pages';
}
