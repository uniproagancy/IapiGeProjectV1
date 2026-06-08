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
    protected $description = 'წაშლის დუბლიკატ პროდუქტებს ka title-ის მიხედვით (უახლესი რჩება) — სურათებითა და დაკავშირებული ჩანაწერებით';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // 1. ka translations დაჯგუფება ნორმალიზებული title-ით
        $rows = ProductTranslation::query()
            ->where('locale', 'ka')
            ->whereNotNull('title')
            ->whereRaw("TRIM(title) != ''")
            ->get(['product_id', 'title']);

        $groups = [];
        foreach ($rows as $r) {
            $key = mb_strtolower(trim($r->title));
            $groups[$key][] = $r->product_id;
        }

        // 2. მხოლოდ დუბლიკატები
        $toDelete  = [];
        $keepCount = 0;
        foreach ($groups as $ids) {
            if (count($ids) < 2) {
                continue;
            }
            sort($ids);
            array_pop($ids);          // უახლესი (MAX id) — რჩება
            $keepCount++;
            foreach ($ids as $oldId) {
                $toDelete[] = $oldId; // ძველები — წაიშლება
            }
        }

        if (empty($toDelete)) {
            $this->info('✅ დუბლიკატები ვერ მოიძებნა.');
            return self::SUCCESS;
        }

        $this->warn("ნაპოვნია " . count($toDelete) . " წასაშლელი დუბლიკატი ({$keepCount} ჯგუფში).");

        if ($dryRun) {
            $this->info('🔍 DRY RUN — არაფერი წაიშალა. წასაშლელი ID-ები:');
            $this->line(implode(', ', array_slice($toDelete, 0, 50)) . (count($toDelete) > 50 ? ' ...' : ''));
            return self::SUCCESS;
        }

        if (!$this->confirm("დარწმუნებული ხართ? " . count($toDelete) . " პროდუქტი (სურათებით) სამუდამოდ წაიშლება!")) {
            $this->info('გაუქმდა.');
            return self::SUCCESS;
        }

        $deleted   = 0;
        $filesGone = 0;

        foreach (array_chunk($toDelete, 100) as $chunk) {
            foreach ($chunk as $id) {
                $product = Product::withTrashed()->find($id);
                if (!$product) {
                    continue;
                }

                // --- ფიზიკური სურათების წაშლა ---
                // main_image
                $filesGone += $this->deleteFile($product->main_image);

                // gallery (db_product_images)
                $imageRows = ProductImage::where('product_id', $id)->withTrashed()->get();
                foreach ($imageRows as $img) {
                    $filesGone += $this->deleteFile($img->path);
                }

                // მთელი პროდუქტის ფოლდერი (uploads/products/{id})
                $dir = "uploads/products/{$id}";
                if (Storage::disk('public')->exists($dir)) {
                    Storage::disk('public')->deleteDirectory($dir);
                }

                // --- DB ჩანაწერების წაშლა ---
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

        Log::warning("🗑️ products:remove-duplicates — წაიშალა {$deleted} დუბლიკატი, {$filesGone} სურათი");
        $this->info("✅ დასრულდა — {$deleted} პროდუქტი, {$filesGone} სურათი წაიშალა.");

        return self::SUCCESS;
    }

    /**
     * ფიზიკური ფაილის წაშლა public disk-დან. აბრუნებს 1 თუ წაიშალა.
     */
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