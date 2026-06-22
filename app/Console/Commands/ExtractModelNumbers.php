<?php

namespace App\Console\Commands;

use App\Models\Product\ProductTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExtractModelNumbers extends Command
{
    protected $signature   = 'products:extract-models {--dry-run : მხოლოდ ჩვენება}';
    protected $description = 'პროდუქტების სახელებიდან მოდელის ნომრის ავტომატური ამოღება';

    private array $patterns = [
        // IS 3042 WH, IS 7262 GY — ასო(ები) + სფეისი + რიცხვები + სფეისი + სუფიქსი
        '/\b([A-Z]{2,4})\s([0-9]{3,4})\s([A-Z]{1,3})\b/',

        // ECAM630.75.TSM, ECAM22.117.B — ასოები+რიცხვები+წერტილები
        '/\b([A-Z]{2,6}[0-9]{2,}(?:\.[0-9A-Z]+){1,})\b/',

        // HV-MS57GT, HV-MS54GT — prefix-dash-letters+numbers
        '/\b([A-Z]{2,4}-[A-Z]{2,4}[0-9]{2,}[A-Z0-9]*)\b/',

        // VP-101, VP-200 — letters-dash-numbers
        '/\b([A-Z]{2,5}-[0-9]{3,}[A-Z0-9]*)\b/',

        // Z1FV000VV, Z1FR0015Y — Apple/MacBook internal codes
        '/\b([A-Z][0-9][A-Z]{2,4}[0-9]{3,}[A-Z]{0,2})\b/',

        // MF200W90WB/T, WF1702WNE/XEG, MG9531/15 — ასოები+რიცხვები+slash
        '/\b([A-Z]{1,5}[0-9]{2,}[A-Z0-9]*(?:[\/\-][A-Z0-9]+)+)\b/',

        // RB38C634DSA/EF, RT47CG6442B1/WT — გრძელი კოდები slash-ით
        '/\b([A-Z]{2,4}[0-9]{2}[A-Z0-9]{4,}(?:\/[A-Z0-9]+)+)\b/',

        // E7GK1-8BP, E6HS1-2EG — ერთი ასო + რიცხვი + dash
        '/\b([A-Z][0-9][A-Z]{2,}[0-9](?:\-[A-Z0-9]+)+)\b/',

        // WK3000WH, SI5006BL, AC5860 — ასოები + 3+ რიცხვი + ასოები
        '/\b([A-Z]{2,4}[0-9]{3,}[A-Z]{1,4}[0-9]*)\b/',

        // MFHP4 — 4+ ასო + 1-2 რიცხვი
        '/\b([A-Z]{4,6}[0-9]{1,2})\b/',

        // J300, JE680, G35, G56 — 1-3 ასო + 2+ რიცხვი
        '/\b([A-Z]{1,3}[0-9]{2,}[A-Z0-9]{0,6})\b/',

        // S6500, D4740 — ერთი ასო + 4+ რიცხვი
        '/\b([A-Z][0-9]{4,}[A-Z0-9]*)\b/',

        // RT47CG6442S9WT, WW80AG6S24ANLD, DV90DG52A0AELE — Samsung/Midea გრძელი კოდები
        '/\b([A-Z]{2,4}[0-9]{2}[A-Z]{1,3}[0-9]{2,}[A-Z0-9]{3,})\b/',

        // MERD86FGG01, MDRD86SLF01 — Midea (ასოები+2ნომ.+ასოები+2ნომ.)
        '/\b([A-Z]{4,6}[0-9]{2}[A-Z]{2,4}[0-9]{2})\b/',

        // R-20GH-WH2 — SHARP R სერია
        '/\b(R-[0-9]{2}[A-Z]{2}(?:-[A-Z0-9]+)+)\b/',
    ];

    // ამ სიტყვებს გამოვტოვებთ (false positives)
    private array $blacklist = [
        'SHARP', 'MIDEA', 'BRAUN', 'PHILIPS', 'SAMSUNG', 'PANASONIC',
        'TEFAL', 'KRUPS', 'MOULINEX', 'ROWENTA', 'ELECTROLUX', 'HOFFMANN',
        'KENWOOD', 'DELONGHI', 'NUTRIBULLET', 'REMINGTON', 'DYSON',
        'SKYTECH', 'MARAZZI', 'THOMAS', 'ARSHIA', 'CUDY', 'TTEC',
        'WHITE', 'BLACK', 'SILVER', 'GREY', 'BLUE', 'GREEN', 'RED',
        'WIFI', 'DUAL', 'BAND', 'SMART', 'PLUS', 'PRO', 'MAX', 'MINI',
        'USB', 'LCD', 'LED', 'RGB', 'TWS', 'ANC', 'PD',
    ];

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');

        if (!$dryRun) {
            if (!\Schema::hasColumn('db_products', 'model_number')) {
                DB::statement('ALTER TABLE db_products ADD COLUMN model_number VARCHAR(100) NULL');
                DB::statement('CREATE INDEX idx_model_number ON db_products(model_number)');
                $this->info('✅ model_number სვეტი შეიქმნა');
            }
        }

        $found    = 0;
        $notFound = 0;

        $translations = ProductTranslation::where('locale', 'ka')
            ->select('product_id', 'title')
            ->get();

        $bar = $this->output->createProgressBar($translations->count());
        $bar->start();

        foreach ($translations as $translation) {
            $model = $this->extractModel($translation->title);

            if ($model) {
                $found++;
                if (!$dryRun) {
                    DB::table('db_products')
                        ->where('id', $translation->product_id)
                        ->whereNull('model_number')
                        ->update(['model_number' => $model]);
                } elseif ($this->option('dry-run')) {
                    $this->line("\n  ✅ [{$translation->product_id}] {$translation->title} → <info>{$model}</info>");
                }
            } else {
                $notFound++;
                if ($dryRun) {
                    $this->line("\n  ❌ [{$translation->product_id}] {$translation->title}");
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $total   = $translations->count();
        $percent = $total > 0 ? round(($found / $total) * 100) : 0;
        $this->info("სულ: {$total} | ✅ {$found} ({$percent}%) | ❌ {$notFound}");
    }

    private function extractModel(string $title): ?string
    {
        // Georgian ტექსტი ამოვიღოთ
        $cleaned = preg_replace('/[\x{10D0}-\x{10FF}]+/u', ' ', $title);
        $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));

        foreach ($this->patterns as $i => $pattern) {
            if (preg_match($pattern, $cleaned, $matches)) {

                // pattern 0 — სფეისიანი (IS 3042 WH → IS3042WH)
                if ($i === 0) {
                    $candidate = $matches[1] . $matches[2] . $matches[3];
                } else {
                    $candidate = $matches[1];
                }

                $candidate = strtoupper(trim($candidate));

                // blacklist შემოწმება
                if (in_array($candidate, $this->blacklist)) continue;

                // ძალიან მოკლე
                if (mb_strlen($candidate) < 3) continue;

                // მხოლოდ ასოები (ბრენდის სახელი)
                if (ctype_alpha($candidate)) continue;

                // მხოლოდ რიცხვები
                if (is_numeric($candidate)) continue;

                return $candidate;
            }
        }

        return null;
    }
}