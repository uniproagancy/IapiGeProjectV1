<?php

namespace App\Models\Payments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentStatusTranslation extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_payment_status_translations";
}
