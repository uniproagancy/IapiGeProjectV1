<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderStatus extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_order_statuses";

    public function translations()
    {
        return $this->hasMany(OrderStatusTranslation::class);
    }

    public function translation($locale = 'ka')
    {
        return $this->translations->where('locale', $locale)->first();
    }
}
