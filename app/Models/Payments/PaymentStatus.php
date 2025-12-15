<?php

namespace App\Models\Payments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentStatus extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_payment_statuses";

    public function translations()
    {
        return $this->hasMany(PaymentStatusTranslation::class);
    }

    public function translation($locale = 'ka')
    {
        return $this->translations->where('locale', $locale)->first();
    }
}
