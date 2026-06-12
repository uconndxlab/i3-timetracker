<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Create a timestamped backup of the SQLite database and prune backups older than 10 days.';

    public function handle(): int
    {
        $connection = config('database.default');
        $dbPath = config("database.connections.{$connection}.database");

        if (! File::exists($dbPath)) {
            $this->error("Database file not found: {$dbPath}");

            return self::FAILURE;
        }

        $exportDir = database_path('exports');
        File::ensureDirectoryExists($exportDir);

        $filename = 'backup_' . Carbon::now()->format('Y-m-d_His') . '.sqlite';
        $destination = $exportDir . DIRECTORY_SEPARATOR . $filename;

        File::copy($dbPath, $destination);
        $this->info("Backup created: {$destination}");

        $cutoff = Carbon::now()->subDays(10)->timestamp;
        $pruned = 0;

        foreach (File::files($exportDir) as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $pruned++;
            }
        }

        if ($pruned > 0) {
            $this->info("Pruned {$pruned} backup(s) older than 10 days.");
        }

        return self::SUCCESS;
    }
}
