<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderStatusTranslation extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_order_status_translations";

}
