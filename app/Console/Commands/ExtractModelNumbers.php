<?php

namespace App\Console\Commands;

use App\Models\Product\Product;
use App\Models\Product\ProductTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExtractModelNumbers extends Command
{
    protected $signature   = 'products:extract-models {--dry-run : მხოლოდ ჩვენება, შენახვის გარეშე}';
    protected $description = 'პროდუქტების სახელებიდან მოდელის ნომრის ავტომატური ამოღება';

    // მოდელის ნომრის პატერნები (პრიორიტეტის მიხედვით)
    private array $patterns = [
        // MF200W90WB/T, WF1702WNE/XEG, MG9531/15
        '/\b([A-Z]{1,5}[0-9]{2,}[A-Z0-9]*(?:[\/\-][A-Z0-9]+)+)\b/',
        // HD9318/10, EP5441/50
        '/\b([A-Z]{2,4}[0-9]{4,}(?:[\/\-][A-Z0-9]+)*)\b/',
        // MF200W90WB, WF70F5E3W4W
        '/\b([A-Z]{1,4}[0-9]{3,}[A-Z0-9]{3,})\b/',
    ];

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? '🔍 Dry-run რეჟიმი — ცვლილება არ შეინახება' : '🚀 მოდელების ამოღება...');

        // migration თუ სვეტი არ არის
        if (!$dryRun) {
            if (!\Schema::hasColumn('db_products', 'model_number')) {
                DB::statement('ALTER TABLE db_products ADD COLUMN model_number VARCHAR(100) NULL');
                DB::statement('CREATE INDEX idx_model_number ON db_products(model_number)');
                $this->info('✅ model_number სვეტი შეიქმნა');
            }
        }

        $found    = 0;
        $notFound = 0;
        $updated  = 0;

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
                    $updated++;
                } else {
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
        $this->info("სულ: {$translations->count()} | მოიძებნა: {$found} | ვერ მოიძებნა: {$notFound}" . (!$dryRun ? " | განახლდა: {$updated}" : ''));
    }

    private function extractModel(string $title): ?string
    {
        // Georgian text-ის სიტყვები გამოვტოვოთ
        $cleaned = preg_replace('/[\x{10D0}-\x{10FF}]+/u', ' ', $title);
        $cleaned = trim($cleaned);

        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $cleaned, $matches)) {
                $candidate = $matches[1];

                // ძალიან მოკლე ან ძალიან გრძელი — გამოვტოვოთ
                if (mb_strlen($candidate) < 4 || mb_strlen($candidate) > 50) continue;

                // მხოლოდ რიცხვები — გამოვტოვოთ
                if (is_numeric($candidate)) continue;

                return strtoupper($candidate);
            }
        }

        return null;
    }
}