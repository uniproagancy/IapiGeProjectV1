<?php

namespace App\Jobs;

use App\Models\EliteProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EliteUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 1;

    public function __construct(public string $filePath) {}

    public function handle(): void
    {
        @ini_set('memory_limit', '512M');

        try {
            $fullPath = Storage::path($this->filePath);

            if (!file_exists($fullPath)) {
                Log::error("⛔ Elite Upload: ფაილი ვერ მოიძებნა", ['path' => $fullPath]);
                return;
            }

            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            $inserted = 0;
            $updated  = 0;
            $skipped  = 0;

            // Excel ფორმატი:
            // A = BarCode (required)
            // B = Item Name (optional)
            // C = Price (optional)
            // D = Quantity (optional)
            // პირველი row = header

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex === 1) continue; // header

                $barCode = trim((string)($row['A'] ?? ''));
                if (!$barCode) {
                    $skipped++;
                    continue;
                }

                $itemName = trim((string)($row['B'] ?? '')) ?: null;
                $price    = isset($row['C']) && is_numeric($row['C']) ? (float)$row['C'] : null;
                $quantity = isset($row['D']) && is_numeric($row['D']) ? (int)$row['D'] : 0;

                $existing = EliteProduct::where('bar_code', $barCode)->first();

                if ($existing) {
                    $existing->update([
                        'item_name' => $itemName ?? $existing->item_name,
                        'price'     => $price ?? $existing->price,
                        'quantity'  => $quantity,
                    ]);
                    $updated++;
                } else {
                    EliteProduct::create([
                        'bar_code'  => $barCode,
                        'item_name' => $itemName,
                        'price'     => $price,
                        'quantity'  => $quantity,
                        'synced'    => false,
                    ]);
                    $inserted++;
                }
            }

            Log::info("✅ Elite Upload დასრულდა", [
                'inserted' => $inserted,
                'updated'  => $updated,
                'skipped'  => $skipped,
            ]);

            Storage::delete($this->filePath);

        } catch (\Throwable $e) {
            Log::error("❌ Elite Upload შეცდომა", [
                'file'  => $this->filePath,
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
            ]);
            throw $e;
        }
    }
}