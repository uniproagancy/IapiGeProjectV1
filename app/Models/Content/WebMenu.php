<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebMenu extends Model
{
    //
    use SoftDeletes;

    protected $table = 'db_menus';

    public function translations()
    {
        return $this->hasMany(WebMenuTranslation::class, 'menu_id', 'id');
    }

    public function translation($locale = 'ka')
    {
        return $this->translations->where('locale', $locale)->first();
    }

}
