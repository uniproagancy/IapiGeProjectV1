<?php

namespace App\Models\Order;

use App\Models\Payments\Payment;
use App\Models\Payments\PaymentStatus;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'db_orders';

    protected $fillable = [
        'user_id',
        'city_id',
        'comment',
        'created_by',
        'payment_id',
        'payment_status_id',
        'amount',
        'delivery_amount',
    ];

    protected $casts = [
        'created_at' => 'datetime', // ან 'datetime:Y-m-d H:i:s'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status_id', 'id');
    }

    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id', 'id');
    }

    public function deliveryData()
    {
        return $this->hasOne(OrderDelivery::class, 'order_id', 'id');
    }
}