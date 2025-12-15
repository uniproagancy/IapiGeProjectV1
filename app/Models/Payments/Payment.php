<?php

namespace App\Models\Payments;

use App\Models\Orders\PaymentTranslation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_payments";

    public function translations()
    {
        return $this->hasMany(PaymentTranslation::class, 'payment_id', 'id');
    }

    public function translation($locale = 'ka')
    {
        return $this->translations->where('locale', $locale)->first();
    }
}
