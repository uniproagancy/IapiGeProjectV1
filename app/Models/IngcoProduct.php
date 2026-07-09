<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngcoProduct extends Model
{
    protected $table = 'db_ingco_products';

    protected $fillable = [
        'sku',
        'stock',
        'price',
        'discount_price',
    ];

    protected $casts = [
        'price'          => 'float',
        'discount_price' => 'float',
        'stock'          => 'integer',
    ];
}