<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class ForgotPasswordHash extends Model
{
    //
    protected $table = "db_forgot_password_hash";

    protected $fillable = ['hash', 'user_id'];
}
