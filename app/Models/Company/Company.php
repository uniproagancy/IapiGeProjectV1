<?php

namespace App\Models\Company;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    //
    use SoftDeletes;

    protected $table = "db_companies";

    protected $fillable = ['user_id', 'code', 'name', 'legal_form_id', 'active', 'phone', 'email'];

    public function legal()
    {
        return $this->hasOne(CompanyLegalForm::class, 'id', 'legal_form_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')->withTrashed();
    }
}
