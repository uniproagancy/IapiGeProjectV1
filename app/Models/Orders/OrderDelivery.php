<?php

namespace App\Models\Orders;

use App\Models\Delivery\DeliveryCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDelivery extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_order_delivery";

    protected $fillable = ['order_id', 'delivery_id', 'address'];

    public function delivery_company()
    {
        return $this->hasOne(DeliveryCompany::class,'id', 'delivery_id');
    }
}
