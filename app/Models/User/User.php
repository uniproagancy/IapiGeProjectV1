<?php

namespace App\Models\User;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Order;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = "db_users";

    protected $fillable = [
        'name', 'lastname', 'email', 'phone', 'password', 'role_id', 'google_id', 'facebook_id', 'avatar', 'email_verified_at',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class,'user_id', 'id');
    }
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wishlistProducts()
    {
        return $this->belongsToMany(Product::class, 'db_wishlists')
            ->withTimestamps();
    }

    public function hasInWishlist($productId)
    {
        return $this->wishlists()->where('product_id', $productId)->exists();
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(UserAddress::class)->where('is_default', true);
    }

    public function getFullNameAttribute()
    {
        return trim($this->name . ' ' . $this->lastname);
    }

    public function hasPassword(): bool
    {
        return !empty($this->password);
    }

    public function hasSocialLogin(): bool
    {
        return !empty($this->google_id) || !empty($this->facebook_id);
    }

}
