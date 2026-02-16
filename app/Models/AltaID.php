<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AltaID extends Model
{
    //
    protected $table = 'db_alta';

    protected $fillable = ['product_id', 'quantity'];

    public $timestamps = false;
}
