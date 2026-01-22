<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class UserVerification extends Model
{
    //
    protected $table = "db_user_verifications";

    protected $fillable = ['user_id', 'code', 'status'];
}
