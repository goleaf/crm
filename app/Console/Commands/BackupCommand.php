<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DataQuality\BackupService;
use Illuminate\Console\Command;

final class BackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:create 
                            {--type=full : The type of backup (full, incremental, differential, database_only, files_only)}
                            {--name= : Custom name for the backup}
                            {--description= : Description for the backup}
                            {--retention-days=30 : Number of days to retain the backup}
                            {--async : Run backup asynchronously}
                            {--verify : Verify backup after creation}';

    /**
     * The console command description.
     */
    protected $description = 'Create a backup of the application data';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $type = $this->option('type');
        $name = $this->option('name') ?? 'CLI Backup ' . now()->format('Y-m-d H:i:s');
        $description = $this->option('description');
        $retentionDays = (int) $this->option('retention-days');
        $async = $this->option('async');
        $verify = $this->option('verify');

        // Validate backup type
        if (! in_array($type, ['full', 'incremental', 'differential', 'database_only', 'files_only'])) {
            $this->error("Invalid backup type: {$type}");

            return self::FAILURE;
        }

        $this->info("Creating {$type} backup: {$name}");

        $config = [
            'type' => $type,
            'name' => $name,
            'description' => $description,
            'retention_days' => $retentionDays,
            'async' => $async,
            'verify' => $verify,
            'files' => [
                'storage/app',
                '.env',
                'composer.json',
                'composer.lock',
            ],
        ];

        try {
            $backupJob = $backupService->createBackup($config);

            if ($backupJob->isCompleted()) {
                $this->info('Backup completed successfully!');
                $this->table(
                    ['Property', 'Value'],
                    [
                        ['ID', $backupJob->id],
                        ['Name', $backupJob->name],
                        ['Type', $backupJob->type->getLabel()],
                        ['Status', $backupJob->status->getLabel()],
                        ['File Size', $backupJob->getFormattedFileSize() ?? 'N/A'],
                        ['Path', $backupJob->backup_path ?? 'N/A'],
                        ['Duration', $backupJob->getDurationInSeconds() ? $backupJob->getDurationInSeconds() . 's' : 'N/A'],
                    ],
                );

                if ($verify && $backupJob->verification_results) {
                    $this->info('Verification Results:');
                    foreach ($backupJob->verification_results as $key => $value) {
                        $status = is_bool($value) ? ($value ? '✓' : '✗') : $value;
                        $this->line("  {$key}: {$status}");
                    }
                }
            } elseif ($backupJob->isFailed()) {
                $this->error('Backup failed: ' . $backupJob->error_message);

                return self::FAILURE;
            } else {
                $this->info("Backup job created and is running in the background (ID: {$backupJob->id})");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to create backup: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
