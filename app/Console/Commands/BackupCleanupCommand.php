<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DataQuality\BackupService;
use Illuminate\Console\Command;

final class BackupCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:cleanup 
                            {--dry-run : Show what would be cleaned up without actually deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up expired backup files';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Cleaning up expired backups...');

        try {
            if ($dryRun) {
                $this->info('DRY RUN - No files will be deleted');
                // In a real implementation, we'd show what would be deleted
                $this->info('This would clean up expired backups');

                return self::SUCCESS;
            }

            $cleanedCount = $backupService->cleanupExpiredBackups();

            if ($cleanedCount > 0) {
                $this->info("Cleaned up {$cleanedCount} expired backup(s)");
            } else {
                $this->info('No expired backups found');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to cleanup backups: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
