<?php

namespace App\Models\Order;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderComment extends Model
{
    use SoftDeletes;

    protected $table = "db_order_comments";

    protected $fillable = [
        'order_id',
        'user_id',
        'comment',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * ✅ ავტორის სახელი — წაშლილი მომხმარებლის შემთხვევაშიც უნდა იმუშაოს
     */
    public function getAuthorNameAttribute(): string
    {
        if (!$this->user) {
            return 'წაშლილი მომხმარებელი';
        }

        return trim($this->user->name . ' ' . $this->user->lastname) ?: $this->user->email;
    }
}
