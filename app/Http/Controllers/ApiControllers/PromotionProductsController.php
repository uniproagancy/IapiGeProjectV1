<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\Product\PromotionProduct;
use Illuminate\Http\Request;

class PromotionProductsController extends Controller
{
    /**
     * ✅ Sync promotion products based on discount_price
     *
     * Logic:
     * - If product HAS discount_price → Add to PromotionProduct
     * - If product LOST discount_price → Remove from PromotionProduct
     */
    public function index(Request $request)
    {
        try {
            // ✅ Get promotion_id from request (optional)
            $promotionId = 1;

            // ✅ Get all products with discount price
            $productsWithDiscount = Product::where('active', 1)
                ->where('in_stock', 1)
                ->where('show', 1)
                ->whereHas('price', function ($query) {
                    $query->where('discount_price', '!=', 0)
                        ->where('discount_price', '!=', null);
                })
                ->pluck('id')
                ->toArray();

            // ✅ Get products currently in promotions (with optional promotion_id filter)
            $query = PromotionProduct::query();
                $query->where('promotion_id', $promotionId);
            $productsInPromotion = $query->pluck('product_id')->toArray();

            // ✅ STEP 1: Add new products to promotion
            $productsToAdd = array_diff($productsWithDiscount, $productsInPromotion);
            foreach ($productsToAdd as $productId) {
                PromotionProduct::create([
                    'product_id' => $productId,
                    'promotion_id' => $promotionId,
                    'created_at' => now(),
                ]);
            }

            // ✅ STEP 2: Remove products that lost discount
            $productsToRemove = array_diff($productsInPromotion, $productsWithDiscount);
            if (!empty($productsToRemove)) {
                $removeQuery = PromotionProduct::whereIn('product_id', $productsToRemove);
                    $removeQuery->where('promotion_id', $promotionId);
                $removeQuery->delete();
            }

            // ✅ Return summary
            return response()->json([
                'status' => 'success',
                'message' => 'Promotion products synced successfully',
                'data' => [
                    'promotion_id' => $promotionId,
                    'added' => count($productsToAdd),
                    'removed' => count($productsToRemove),
                    'total_in_promotion' => count($productsWithDiscount),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ Get all promotion products
     */
    public function getPromotionProducts(Request $request)
    {
        try {
            $promotionId = 1; // ✅ Optional filter

            $query = PromotionProduct::with(['product' => function ($query) {
                $query->select('id', 'name', 'sku')
                    ->with(['price:product_id,regular_price,discount_price', 'translations:id,product_id,title,slug']);
            }]);

            $query->where('promotion_id', $promotionId);

            $products = $query->get();

            return response()->json([
                'status' => 'success',
                'promotion_id' => $promotionId,
                'data' => $products,
                'count' => $products->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ Manually add product to promotion
     */
    public function addToPromotion(Request $request)
    {
        try {
            $productId = $request->input('product_id');
            $promotionId = 1; // ✅ Optional

            // Check if product exists
            $product = Product::find($productId);
            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                ], 404);
            }

            // Check if product already in promotion (with optional promotion_id filter)
            $query = PromotionProduct::where('product_id', $productId);
            if ($promotionId) {
                $query->where('promotion_id', $promotionId);
            }
            if ($query->exists()) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Product already in this promotion',
                ], 200);
            }

            // Check if product has discount
            if (!$product->price ||
                !$product->price->discount_price ||
                $product->price->discount_price == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product must have discount price',
                ], 400);
            }

            // Add to promotion
            PromotionProduct::create([
                'product_id' => $productId,
                'promotion_id' => $promotionId,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Product added to promotion',
                'data' => [
                    'product_id' => $productId,
                    'promotion_id' => $promotionId,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ Manually remove product from promotion
     */
    public function removeFromPromotion(Request $request, $productId)
    {
        try {
            $promotionId = 1; // ✅ Optional

            $query = PromotionProduct::where('product_id', $productId);
            if ($promotionId) {
                $query->where('promotion_id', $promotionId);
            }
            $promotion = $query->first();

            if (!$promotion) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not in this promotion',
                ], 404);
            }

            $promotion->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Product removed from promotion',
                'data' => [
                    'product_id' => $productId,
                    'promotion_id' => $promotionId,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ Check if product is in promotion
     */
    public function isInPromotion(Request $request, $productId)
    {
        try {
            $promotionId = 1; // ✅ Optional

            $query = PromotionProduct::where('product_id', $productId);
            if ($promotionId) {
                $query->where('promotion_id', $promotionId);
            }
            $exists = $query->exists();

            return response()->json([
                'status' => 'success',
                'product_id' => $productId,
                'promotion_id' => $promotionId,
                'in_promotion' => $exists,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ Reset promotions (clear everything or specific promotion)
     */
    public function reset(Request $request)
    {
        try {
            $promotionId = 1; // ✅ Optional

            if ($promotionId) {
                // Delete only specific promotion
                PromotionProduct::where('promotion_id', $promotionId)->delete();
                $message = "Promotion products for promotion_id: {$promotionId} cleared";
            } else {
                // Delete all promotions
                PromotionProduct::truncate();
                $message = "All promotion products cleared";
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'promotion_id' => $promotionId,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}