<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;

class OrderPixelData extends Model
{
    protected $table = 'db_order_pixel_data';

    protected $fillable = [
        'order_id',
        'event_id',
        'fbp',
        'fbc',
        'client_ip',
        'client_user_agent',
        'purchase_event_id',
        'em',
        'ph',
        'fn',
        'ln',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}