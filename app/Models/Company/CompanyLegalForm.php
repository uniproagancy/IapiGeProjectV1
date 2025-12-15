<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyLegalForm extends Model
{
    //
    use SoftDeletes;

    protected $table = 'db_company_legal_form';
}
