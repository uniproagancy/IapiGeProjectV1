<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart\ShoppingCart;
use App\Models\Product\Product;
use App\Services\Facebook\FacebookPixelService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function add(Request $request)
    {
        $productId = (int) $request->input('product_id');
        $quantity  = max(1, (int) $request->input('quantity', 1));

        try {
            $product = Product::with(['translations', 'price'])->findOrFail($productId);

            if (!$product->price) {
                return response()->json(['success' => false, 'message' => 'პროდუქტის ფასი არ არის მიუთითებული!'], 422);
            }

            $translation = $product->translations->where('locale', app()->getLocale())->first()
                ?? $product->translations->where('locale', 'ka')->first();

            $price = ($product->price->discount_price && $product->price->discount_price != '0.0')
                ? $product->price->discount_price
                : $product->price->regular_price;

            $existing = Cart::get($product->id);
            if ($existing) {
                Cart::update($product->id, [
                    'quantity' => ['relative' => false, 'value' => $existing->quantity + $quantity]
                ]);
            } else {
                Cart::add([
                    'id'         => $product->id,
                    'name'       => $translation->title,
                    'price'      => $price,
                    'quantity'   => $quantity,
                    'attributes' => [
                        'image'            => $product->main_image,
                        'slug'             => $translation->slug,
                        'sku'              => $product->sku,
                        'discount_percent' => $product->price->discount_percent ?? null,
                        'regular_price'    => $product->price->regular_price,
                    ],
                ]);
            }

            $this->syncCartToDatabase();

            // Server-side Facebook Pixel
            $cartEventId = 'ac_' . time() . '_' . Str::random(6);
            $sourceUrl   = $request->input('source_url', url()->current());

            app(FacebookPixelService::class)->trackAddToCart(
                product: ['id' => $product->id, 'name' => $translation->title, 'quantity' => $quantity],
                value: $price * $quantity,
                currency: 'GEL',
                params: ['event_source_url' => $sourceUrl],
                eventId: $cartEventId,
            );

            return response()->json([
                'success'      => true,
                'message'      => 'კალათაში დაემატა!',
                'cart_count'   => Cart::getTotalQuantity(),
                'product_name' => $translation->title,
                'price'        => $price,
                'event_id'     => $cartEventId,
                'product_id'   => $product->id,
                'quantity'     => $quantity,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'პროდუქტი ნაპოვნი არ არის!'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'შეცდომა კალათაში დამატებისას!'], 500);
        }
    }

    private function syncCartToDatabase(): void
    {
        try {
            $userId      = auth()->id();
            $sessionId   = session()->getId();
            $cartContent = Cart::getContent();

            if (empty($cartContent)) {
                $userId
                    ? ShoppingCart::where('user_id', $userId)->delete()
                    : ShoppingCart::where('session_id', $sessionId)->delete();
                return;
            }

            if ($userId) {
                ShoppingCart::where('user_id', $userId)->delete();
                foreach ($cartContent as $item) {
                    ShoppingCart::create([
                        'user_id'    => $userId,
                        'product_id' => $item->id,
                        'name'       => $item->name,
                        'price'      => $item->price,
                        'quantity'   => $item->quantity,
                        'attributes' => $item->attributes->toArray(),
                    ]);
                }
            } else {
                ShoppingCart::where('session_id', $sessionId)->delete();
                foreach ($cartContent as $item) {
                    ShoppingCart::create([
                        'session_id' => $sessionId,
                        'product_id' => $item->id,
                        'name'       => $item->name,
                        'price'      => $item->price,
                        'quantity'   => $item->quantity,
                        'attributes' => $item->attributes->toArray(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // silent
        }
    }
}