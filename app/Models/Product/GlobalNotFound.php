<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class GlobalNotFound extends Model
{
    protected $table = 'db_global_not_found';

    protected $fillable = ['name', 'stock', 'price', 'reason'];
}