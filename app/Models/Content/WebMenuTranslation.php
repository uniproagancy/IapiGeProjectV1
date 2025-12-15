<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebMenuTranslation extends Model
{
    //
    use SoftDeletes;

    protected $table = 'db_menu_translations';
}
