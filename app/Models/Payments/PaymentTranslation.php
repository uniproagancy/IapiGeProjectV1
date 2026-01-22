<?php

namespace App\Models\Payments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentTranslation extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_payment_translations";
}
