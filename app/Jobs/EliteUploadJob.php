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
        @set_time_limit(0);  // ← დაამატე

        try {
            $fullPath = Storage::path($this->filePath);

            if (!file_exists($fullPath)) {
                Log::error("⛔ Elite Upload: ფაილი ვერ მოიძებნა", ['path' => $fullPath]);
                return;
            }

            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();

            $inserted = 0;
            $skipped  = 0;
            $duplicate = 0;

            // Excel ფორმატი: A სვეტი = BarCode (header-ის გარეშე)
            foreach ($sheet->getRowIterator() as $row) {
                $cell = $sheet->getCell('A' . $row->getRowIndex());
                $barCode = trim((string) $cell->getValue());

                if (!$barCode) {
                    $skipped++;
                    continue;
                }

                $existing = EliteProduct::where('bar_code', $barCode)->first();

                if ($existing) {
                    $duplicate++;
                    continue;
                }

                EliteProduct::create([
                    'bar_code' => $barCode,
                    'synced'   => false,
                ]);
                $inserted++;
            }

            Log::info("✅ Elite Upload დასრულდა", [
                'inserted'  => $inserted,
                'duplicate' => $duplicate,
                'skipped'   => $skipped,
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