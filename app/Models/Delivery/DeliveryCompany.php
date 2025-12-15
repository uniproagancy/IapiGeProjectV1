<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryCompany extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_delivery_companies";
}
