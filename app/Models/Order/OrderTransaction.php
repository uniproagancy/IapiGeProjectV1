<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderTransaction extends Model
{
    //
    use SoftDeletes;

    protected $table = 'db_order_transactions';

    protected $fillable = [
        'order_id',
        'payment_order_id',
        'url',
        'amount',
        'status',
        'response',
        'type'
    ];
}
