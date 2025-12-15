<?php

namespace App\Models\Cart;

use App\Models\Product\Product;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingCart extends Model
{

    protected $table = 'db_shopping_carts';

    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
        'name',
        'price',
        'quantity',
        'attributes',
    ];

    protected $casts = [
        'attributes' => 'array',
        'price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}