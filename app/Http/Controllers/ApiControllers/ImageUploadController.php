<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageUploadController extends Controller
{
    /**
     * POST /api/upload/image
     * Body: { product_id: int, image_url: string, secret: string }
     */
    public function upload(Request $request)
    {
        // Secret key შემოწმება
        if ($request->input('secret') !== env('UPLOAD_SECRET', 'alta_upload_secret_2026')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $productId = (int) $request->input('product_id');
        $imageUrl  = $request->input('image_url');

        if (!$productId || !$imageUrl) {
            return response()->json(['error' => 'product_id და image_url საჭიროა'], 400);
        }

        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['error' => "Product #{$productId} ვერ მოიძებნა"], 404);
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get($imageUrl);

            if (!$response->successful()) {
                return response()->json(['error' => 'სურათი ვერ ჩამოიტვირთა'], 500);
            }

            $ext  = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg');
            $ext  = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) ? $ext : 'jpg';
            $path = "uploads/products/{$productId}/main_{$productId}.{$ext}";

            Storage::disk('public')->put($path, $response->body());
            $product->update(['main_image' => $path]);

            Log::info("📸 ImageUpload: სურათი შენახულია product_id={$productId}");

            return response()->json([
                'success' => true,
                'path'    => $path,
            ]);

        } catch (\Throwable $e) {
            Log::error("❌ ImageUpload error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}