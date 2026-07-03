<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanLivewireTmp extends Command
{
    protected $signature = 'livewire:cleanup';
    protected $description = 'Clean up old Livewire temporary upload files';

    public function handle(): int
    {
        $disk = Storage::disk(config('livewire.temporary_file_upload.disk') ?: config('filesystems.default'));
        $directory = config('livewire.temporary_file_upload.directory') ?: 'livewire-tmp';

        if (!$disk->exists($directory)) {
            $this->info('No livewire-tmp directory found.');
            return self::SUCCESS;
        }

        $deleted = 0;
        $yesterday = now()->subDay()->timestamp;

        foreach ($disk->files($directory) as $file) {
            try {
                if ($yesterday > $disk->lastModified($file)) {
                    $disk->delete($file);
                    $deleted++;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        $this->info("Deleted {$deleted} old temporary files.");

        return self::SUCCESS;
    }
}
