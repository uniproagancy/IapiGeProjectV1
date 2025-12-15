<?php

namespace App\Traits;

use App\Models\Product\Product;
use App\Models\User\Wishlist;

trait WithWishlist
{
    public function toggleWishlist($productId)
    {
        try {
            if (!auth()->check()) {
                $this->dispatch('notify', message: 'გთხოვთ გაიაროთ ავტორიზაცია', type: 'warning');
                return redirect()->route('web.user.sign_in');
            }
            if (!Product::where('id', $productId)->exists()) {
                $this->dispatch('notify', message: 'პროდუქტი ნაპოვნი არ არის!', type: 'error');
                return;
            }
            $userId = auth()->id();
            $wishlist = Wishlist::where('user_id', $userId)
                ->where('product_id', $productId)
                ->first();

            if ($wishlist) {
                $wishlist->delete();
                $this->dispatch('wishlistUpdated');
                $this->dispatch('notify', message: 'პროდუქტი წაიშალა სურვილების სიიდან', type: 'info');
                Log::info("Product removed from wishlist: user={$userId}, product={$productId}");
            } else {
                Wishlist::create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                ]);
                $this->dispatch('wishlistUpdated');
                $this->dispatch('notify', message: 'პროდუქტი დაემატა სურვილების სიაში!', type: 'success');
                Log::info("Product added to wishlist: user={$userId}, product={$productId}");
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'შეცდომა სურვილების სიის განახლებისას!', type: 'error');
        }
    }

    /**
     * ✅ Remove product from wishlist
     */
    public function removeFromWishlist($productId)
    {
        try {
            if (!auth()->check()) {
                return;
            }
            $userId = auth()->id();
            $deleted = Wishlist::where('user_id', $userId)
                ->where('product_id', $productId)
                ->delete();
            if ($deleted) {
                $this->dispatch('wishlistUpdated');
                $this->dispatch('notify', message: 'პროდუქტი წაიშალა სურვილების სიიდან', type: 'info');
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'შეცდომა წაშლის დროს!', type: 'error');
        }
    }

    public function isInWishlist($productId): bool
    {
        try {
            if (!auth()->check()) {
                return false;
            }

            return Wishlist::where('user_id', auth()->id())
                ->where('product_id', $productId)
                ->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getWishlistCount(): int
    {
        try {
            if (!auth()->check()) {
                return 0;
            }
            return Wishlist::where('user_id', auth()->id())->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getWishlistProducts()
    {
        try {
            if (!auth()->check()) {
                return collect();
            }

            return Wishlist::where('user_id', auth()->id())
                ->with(['product' => function ($query) {
                    $query->with(['translations', 'price', 'images']);
                }])
                ->get()
                ->pluck('product');
        } catch (\Exception $e) {
            return collect();
        }
    }

    public function clearWishlist()
    {
        try {
            if (!auth()->check()) {
                return;
            }
            $userId = auth()->id();
            $count = Wishlist::where('user_id', $userId)->count();
            Wishlist::where('user_id', $userId)->delete();
            $this->dispatch('wishlistUpdated');
            $this->dispatch('notify', message: "სურვილების სია გაიწმინდა ({$count} პროდუქტი)", type: 'info');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'შეცდომა სურვილების სის გასუფთავებისას!', type: 'error');
        }
    }

    public function addMultipleToWishlist(array $productIds)
    {
        try {
            if (!auth()->check()) {
                $this->dispatch('notify', message: 'გთხოვთ გაიაროთ ავტორიზაცია', type: 'warning');
                return;
            }
            $userId = auth()->id();
            $addedCount = 0;
            foreach ($productIds as $productId) {
                $exists = Wishlist::where('user_id', $userId)
                    ->where('product_id', $productId)
                    ->exists();
                if (!$exists) {
                    Wishlist::create([
                        'user_id' => $userId,
                        'product_id' => $productId,
                    ]);
                    $addedCount++;
                }
            }
            if ($addedCount > 0) {
                $this->dispatch('wishlistUpdated');
                $this->dispatch('notify', message: "{$addedCount} პროდუქტი დაემატა სურვილების სიაში!", type: 'success');
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'შეცდომა დამატებისას!', type: 'error');
        }
    }
}