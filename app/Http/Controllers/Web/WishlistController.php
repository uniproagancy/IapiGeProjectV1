<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\User\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function toggle(Request $request)
    {
        if (!auth()->check()) {
            return response()->json([
                'success'  => false,
                'auth'     => false,
                'message'  => 'გთხოვთ გაიაროთ ავტორიზაცია',
                'redirect' => route('web.user.sign_in'),
            ], 401);
        }

        $productId = (int) $request->input('product_id');

        if (!Product::where('id', $productId)->exists()) {
            return response()->json(['success' => false, 'message' => 'პროდუქტი ნაპოვნი არ არის!'], 404);
        }

        $userId   = auth()->id();
        $wishlist = Wishlist::where('user_id', $userId)->where('product_id', $productId)->first();

        if ($wishlist) {
            $wishlist->delete();
            return response()->json([
                'success'       => true,
                'in_wishlist'   => false,
                'message'       => 'პროდუქტი წაიშალა სურვილების სიიდან',
            ]);
        }

        Wishlist::create(['user_id' => $userId, 'product_id' => $productId]);

        return response()->json([
            'success'       => true,
            'in_wishlist'   => true,
            'message'       => 'პროდუქტი დაემატა სურვილების სიაში!',
        ]);
    }

    /**
     * GET /wishlist/check?ids=1,2,3,4
     * ერთი request — ყველა product_id-ისთვის
     */
    public function check(Request $request)
    {
        if (!auth()->check()) {
            return response()->json([]);
        }

        $ids = array_filter(array_map('intval', explode(',', $request->input('ids', ''))));

        if (empty($ids)) {
            return response()->json([]);
        }

        $wishlistIds = Wishlist::where('user_id', auth()->id())
            ->whereIn('product_id', $ids)
            ->pluck('product_id')
            ->toArray();

        return response()->json($wishlistIds);
    }
}