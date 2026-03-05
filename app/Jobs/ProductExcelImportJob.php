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
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductExcelImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    public function __construct(
        protected string $filePath
    ) {}

    public function handle(): void
    {
        $fullPath    = Storage::disk('public')->path($this->filePath);
        $spreadsheet = IOFactory::load($fullPath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray();

        $stats = [
            'updated'  => 0,
            'skipped'  => 0,
            'notfound' => 0,
        ];

        foreach ($rows as $index => $row) {
            // ✅ Row 1 = headers
            if ($index === 0) {
                continue;
            }

            $name          = trim($row[0] ?? '');
            $price         = (float) ($row[1] ?? 0);
            $qtyRaw        = $row[2] ?? 0;
            $discountPrice = !empty($row[3]) ? (float) $row[3] : null;

            if (empty($name)) {
                $stats['skipped']++;
                continue;
            }

            // ✅ '10+' → 10, 0 → 0
            $qty = is_numeric($qtyRaw)
                ? (int) $qtyRaw
                : (int) filter_var($qtyRaw, FILTER_SANITIZE_NUMBER_INT);

            // ✅ LIKE ძებნა name-ით + მხოლოდ GL- SKU
            $product = Product::whereHas('translations', function ($q) use ($name) {
                $q->where('locale', 'ka')
                    ->where('title', 'like', '%' . trim($name) . '%');
            })->where('sku', 'LIKE', '%GL-%')->first();

            if (!$product) {
                Log::info("⏭️ Not found: {$name}");
                $stats['notfound']++;
                continue;
            }

            // ✅ ნაშთის განახლება
            $product->update([
                'quantity' => $qty,
                'in_stock' => $qty > 0 ? 1 : 0,
            ]);

            // ✅ ფასის განახლება
            if ($price > 0) {
                ProductPrice::where('product_id', $product->id)->update([
                    'dealer_price'   => $price,
                    'regular_price'  => $price,
                    'discount_price' => $discountPrice,
                ]);
            }

            Log::info("✅ Updated: {$name} | ProductID: {$product->id} | qty: {$qty} | price: {$price} | discount: " . ($discountPrice ?? 'null'));
            $stats['updated']++;
        }

        Log::info('📊 Excel import done', $stats);

        // ✅ public disk — შეცვლილია local-დან
        Storage::disk('public')->delete($this->filePath);
    }
}