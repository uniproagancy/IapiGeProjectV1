<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EliteProduct extends Model
{
    protected $table = 'db_elite_products';

    protected $fillable = [
        'bar_code',
        'item_name',
        'price',
        'quantity',
        'synced',
        'product_id',
    ];

    protected $casts = [
        'synced'   => 'boolean',
        'price'    => 'decimal:2',
        'quantity' => 'integer',
    ];
}