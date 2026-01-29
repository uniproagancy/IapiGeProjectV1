<?php

namespace App\Traits;

use App\Models\Cart\ShoppingCart;
use App\Models\Product\Product;
use Darryldecode\Cart\Facades\CartFacade as Cart;

trait WithCart
{
    public function addToCart($productId, $quantity = 1)
    {
        try {
            if ($quantity < 1) {
                $this->dispatch('ui:error', message: 'რაოდენობა უნდა იყოს 1 ან მეტი!', type: 'error');
                return;
            }
            $product = Product::with(['translations', 'price'])->findOrFail($productId);
            if (!$product->price) {
                $this->dispatch('ui:error', message: 'პროდუქტის ფასი არ არის მიუთითებული!', type: 'error');
                return;
            }
            $translation = $product->translation(app()->getLocale()) ?? $product->translation('ka');
			if($product->price->discount_price != '0.0') {
				$price = $product->price->discount_price;
			} else {
				$price = $product->price->regular_price;
			}
            $existingItem = Cart::get($product->id);
            if ($existingItem) {
                $this->updateCartQuantity($product->id, $existingItem->quantity + $quantity);
                return;
            }
            Cart::add([
                'id' => $product->id,
                'name' => $translation->title,
                'price' => $price,
                'quantity' => $quantity,
                'attributes' => [
                    'image' => $product->main_image,
                    'slug' => $translation->slug,
                    'sku' => $product->sku,
                    'discount_percent' => $product->price->discount_percent ?? null,
                    'regular_price' => $product->price->regular_price,
                ]
            ]);
            $this->syncCartToDatabase();
            $this->dispatch('cartUpdated');
            $this->dispatch('ui:success', message: 'პროდუქტი დაემატა კალათაში!', type: 'success');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->dispatch('ui:error', message: 'პროდუქტი ნაპოვნი არ არის!', type: 'error');
        } catch (\Exception $e) {
            $this->dispatch('ui:error', message: 'შეცდომა კალათაში დამატებისას!', type: 'error');
        }
    }

    public function updateCartQuantity($itemId, $quantity)
    {
        try {
            if ($quantity < 1) {
                return $this->removeFromCart($itemId);
            }
            Cart::update($itemId, [
                'quantity' => [
                    'relative' => false,
                    'value' => $quantity
                ]
            ]);
            $this->syncCartToDatabase();
            $this->dispatch('cartUpdated');
        } catch (\Exception $e) {
            Log::error("Error updating cart quantity: " . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეცდომა რაოდენობის განახლებისას!', type: 'error');
        }
    }

    public function removeFromCart($itemId)
    {
        try {
            Cart::remove($itemId);
            $this->syncCartToDatabase();
            $this->dispatch('cartUpdated');
            $this->dispatch('ui:success', message: 'პროდუქტი წაიშალა კალათიდან!', type: 'success');
        } catch (\Exception $e) {
            Log::error("Error removing from cart: " . $e->getMessage());
            $this->dispatch('ui:error', message: 'შეცდომა წაშლის დროს!', type: 'error');
        }
    }

    public function clearCart()
    {
        try {
            Cart::clear();
            $this->syncCartToDatabase();
            $this->dispatch('cartUpdated');
            $this->dispatch('ui:success', message: 'კალათა გაიწმინდა!', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('ui:error', message: 'შეცდომა კალათის გასუფთავებისას!', type: 'error');
        }
    }

    public function getCartCount()
    {
        try {
            return Cart::getTotalQuantity();
        } catch (\Exception $e) {
            Log::error("Error getting cart count: " . $e->getMessage());
            return 0;
        }
    }

    public function getCartTotal()
    {
        try {
            return Cart::getTotal();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getCartSubTotal()
    {
        try {
            return Cart::getSubTotal();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getCartItems()
    {
        try {
            return Cart::getContent();
        } catch (\Exception $e) {
            return collect();
        }
    }

    protected function syncCartToDatabase()
    {
        try {
            $userId = auth()->id();
            $sessionId = session()->getId();
            $cartContent = Cart::getContent();
            if (empty($cartContent)) {
                if ($userId) {
                    ShoppingCart::where('user_id', $userId)->delete();
                } else {
                    ShoppingCart::where('session_id', $sessionId)->delete();
                }
                return;
            }

            if ($userId) {
                ShoppingCart::where('user_id', $userId)->delete();
                foreach ($cartContent as $item) {
                    ShoppingCart::create([
                        'user_id' => $userId,
                        'product_id' => $item->id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'attributes' => $item->attributes->toArray(),
                    ]);
                }
            } else {
                ShoppingCart::where('session_id', $sessionId)->delete();
                foreach ($cartContent as $item) {
                    ShoppingCart::create([
                        'session_id' => $sessionId,
                        'product_id' => $item->id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'attributes' => $item->attributes->toArray(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            return;
        }
    }

    public function loadCartFromDatabase()
    {
        try {
            if (!Cart::getContent()->isEmpty()) {

                Cart::clear();
                $userId = auth()->id();
                $sessionId = session()->getId();
                if ($userId) {
                    $items = ShoppingCart::where('user_id', $userId)->get();
                } else {
                    $items = ShoppingCart::where('session_id', $sessionId)->get();
                }
                if ($items->isEmpty()) {
                    return;
                }
                foreach ($items as $item) {
                    Cart::add([
                        'id' => $item->product_id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'attributes' => $item->attributes,
                    ]);
                }
                $this->dispatch('cartUpdated');
            }

        } catch (\Exception $e) {
        }
    }
}