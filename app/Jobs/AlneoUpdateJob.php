<?php

namespace App\Jobs;

use App\Models\Product\Product;
use App\Models\Product\ProductPrice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AlneoUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        public string  $sku,
        public int     $stock,
        public float   $price,
        public ?float  $discountPrice = null,
    ) {}

    public function handle(): void
    {
        $fullSku = 'ALNEO-' . $this->sku;

        $product = Product::where('sku', $fullSku)
            ->where('supplier_id', 10)
            ->first();

        if (!$product) {
            Log::warning("⚠️ Alneo Excel: პროდუქტი ვერ მოიძებნა | sku={$fullSku}");
            return;
        }

        if ($product->update_lock) {
            Log::info("🔒 Alneo Excel: ჩაკეტილია, გამოტოვება | sku={$fullSku}");
            return;
        }

        $inStock = $this->stock > 0 ? 1 : 0;

        $updateData = [
            'quantity' => $this->stock,
            'in_stock' => $inStock,
            'show'     => $inStock,
            'active'   => 1,
        ];

        $product->update($updateData);

        $discountPercent = 0;
        if ($this->discountPrice > 0 && $this->price > 0 && $this->price > $this->discountPrice) {
            $discountPercent = (int) round((($this->price - $this->discountPrice) / $this->price) * 100);
        }

        ProductPrice::updateOrCreate(
            ['product_id' => $product->id],
            [
                'dealer_price'     => $this->price,
                'regular_price'    => $this->price,
                'discount_price'   => $this->discountPrice > 0 ? $this->discountPrice : null,
                'discount_percent' => $discountPercent,
            ]
        );

        Log::info("✅ Alneo Excel: განახლდა | sku={$fullSku} | stock={$this->stock} | price={$this->price}" .
            ($this->discountPrice ? " | discount={$this->discountPrice}" : ''));
    }
}