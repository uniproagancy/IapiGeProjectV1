<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlneoProduct extends Model
{
    protected $table    = 'db_alneo_products';
    protected $fillable = ['sku', 'stock', 'price', 'discount_price'];
}