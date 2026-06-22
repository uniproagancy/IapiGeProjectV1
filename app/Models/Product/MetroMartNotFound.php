<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class MetroMartNotFound extends Model
{
    protected $table = 'db_metro_mart_not_found';

    protected $fillable = ['name', 'stock', 'price', 'reason'];
}