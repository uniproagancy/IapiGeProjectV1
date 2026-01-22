<?php

namespace App\Models\Order;

use App\Models\Delivery\City;
use App\Models\Delivery\DeliveryCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDelivery extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_order_delivery";

    protected $fillable = ['order_id', 'delivery_id', 'address', 'city_id'];


    public function city()
    {
        return $this->hasOne(City::class, 'id', 'city_id');
    }

    public function delivery_company()
    {
        return $this->hasOne(DeliveryCompany::class,'id', 'delivery_id');
    }
}
