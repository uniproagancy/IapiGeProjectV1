<?php

namespace App\Console\Commands;

use App\Models\Product\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanProductFolders extends Command
{
    protected $signature   = 'products:clean-folders';
    protected $description = 'წაშლის storage ფოლდერებს რომელთა ID არ არის DB-ში';

    public function handle(): void
    {
        // ✅ DB-დან არსებული ID-ების წამოღება
        $existingIds = Product::withTrashed()->pluck('id')->map(fn($id) => (string) $id)->toArray();

        // ✅ storage-ში არსებული ფოლდერების წამოღება
        $folders = Storage::disk('public')->directories('uploads/products');

        $deleted = 0;
        $skipped = 0;

        foreach ($folders as $folder) {
            $folderId = basename($folder);

            if (!in_array($folderId, $existingIds)) {
                Storage::disk('public')->deleteDirectory($folder);
                $this->line("🗑️  წაიშალა: {$folder}");
                $deleted++;
            } else {
                $skipped++;
            }
        }

        $this->info("✅ დასრულდა — წაიშალა: {$deleted}, დარჩა: {$skipped}");
    }
}