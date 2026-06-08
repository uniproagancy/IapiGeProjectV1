<?php
// app/Console/Commands/RemoveDuplicateProducts.php

namespace App\Console\Commands;

use App\Models\Product\Product;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RemoveDuplicateProducts extends Command
{
    protected $signature = 'products:remove-duplicates {--dry-run : მხოლოდ ჩვენება, არ წაშალო}';
    protected $description = 'წაშლის Grandel (supplier_id=7) დუბლიკატებს ka title-ის მიხედვით, სურათებითურთ';

    private const TARGET_SUPPLIER = 7;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // 1. ka translations — title + product_id + supplier_id
        $rows = ProductTranslation::query()
            ->where('db_product_translations.locale', 'ka')
            ->whereNotNull('db_product_translations.title')
            ->whereRaw("TRIM(db_product_translations.title) != ''")
            ->join('db_products', 'db_products.id', '=', 'db_product_translations.product_id')
            ->get([
                'db_product_translations.product_id',
                'db_product_translations.title',
                'db_products.supplier_id',
            ]);

        // 2. დაჯგუფება ნორმალიზებული title-ით
        $groups = [];
        foreach ($rows as $r) {
            $key = mb_strtolower(trim($r->title));
            $groups[$key][] = [
                'id'       => (int) $r->product_id,
                'supplier' => (int) $r->supplier_id,
            ];
        }

        // 3. წასაშლელების გამოთვლა
        $toDelete = [];
        foreach ($groups as $items) {
            if (count($items) < 2) {
                continue; // დუბლიკატი არ არის
            }

            $grandel = array_filter($items, fn($i) => $i['supplier'] === self::TARGET_SUPPLIER);
            $others  = array_filter($items, fn($i) => $i['supplier'] !== self::TARGET_SUPPLIER);

            if (empty($grandel)) {
                continue; // ამ ჯგუფში Grandel არ არის — არ ვეხებით
            }

            $grandelIds = array_column($grandel, 'id');
            sort($grandelIds);

            if (!empty($others)) {
                // სხვა supplier-იც არის → ყველა Grandel წაიშალოს
                foreach ($grandelIds as $id) {
                    $toDelete[] = $id;
                }
            } else {
                // მხოლოდ Grandel-ებია → ერთი (უახლესი) დარჩეს
                array_pop($grandelIds); // MAX id რჩება
                foreach ($grandelIds as $id) {
                    $toDelete[] = $id;
                }
            }
        }

        $toDelete = array_values(array_unique($toDelete));

        if (empty($toDelete)) {
            $this->info('✅ Grandel დუბლიკატები ვერ მოიძებნა.');
            return self::SUCCESS;
        }

        $this->warn("ნაპოვნია " . count($toDelete) . " წასაშლელი Grandel დუბლიკატი.");

        if ($dryRun) {
            $this->info('🔍 DRY RUN — არაფერი წაიშალა. წასაშლელი ID-ები:');
            $this->line(implode(', ', array_slice($toDelete, 0, 80)) . (count($toDelete) > 80 ? ' ...' : ''));
            return self::SUCCESS;
        }

        if (!$this->confirm("დარწმუნებული ხართ? " . count($toDelete) . " Grandel პროდუქტი (სურათებით) სამუდამოდ წაიშლება!")) {
            $this->info('გაუქმდა.');
            return self::SUCCESS;
        }

        $deleted   = 0;
        $filesGone = 0;

        foreach (array_chunk($toDelete, 100) as $chunk) {
            foreach ($chunk as $id) {
                $product = Product::withTrashed()->find($id);

                // უსაფრთხოების გადამოწმება — მხოლოდ supplier_id=7
                if (!$product || (int) $product->supplier_id !== self::TARGET_SUPPLIER) {
                    continue;
                }

                // --- ფიზიკური სურათები ---
                $filesGone += $this->deleteFile($product->main_image);

                $imageRows = ProductImage::where('product_id', $id)->withTrashed()->get();
                foreach ($imageRows as $img) {
                    $filesGone += $this->deleteFile($img->path);
                }

                $dir = "uploads/products/{$id}";
                if (Storage::disk('public')->exists($dir)) {
                    Storage::disk('public')->deleteDirectory($dir);
                }

                // --- DB ჩანაწერები ---
                DB::transaction(function () use ($id, $product, &$deleted) {
                    ProductImage::where('product_id', $id)->forceDelete();
                    ProductPrice::where('product_id', $id)->forceDelete();
                    ProductTranslation::where('product_id', $id)->forceDelete();
                    $product->forceDelete();
                    $deleted++;
                });
            }
            $this->info("წაიშალა {$deleted} პროდუქტი, {$filesGone} ფაილი...");
        }

        Log::warning("🗑️ remove-duplicates (Grandel) — წაიშალა {$deleted} პროდუქტი, {$filesGone} სურათი");
        $this->info("✅ დასრულდა — {$deleted} Grandel პროდუქტი, {$filesGone} სურათი წაიშალა.");

        return self::SUCCESS;
    }

    private function deleteFile(?string $path): int
    {
        if (empty($path) || $path === '1') {
            return 0;
        }
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                return 1;
            }
        } catch (\Throwable $e) {
            Log::warning("⚠️ ფაილის წაშლა ვერ მოხერხდა [{$path}]: " . $e->getMessage());
        }
        return 0;
    }
}