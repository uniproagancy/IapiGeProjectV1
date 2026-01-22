<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaticPage extends Model
{
    //
    use SoftDeletes;

    protected $table = 'db_static_pages';
}
