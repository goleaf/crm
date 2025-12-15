<?php

declare(strict_types=1);

namespace App\Services\DataQuality;

use App\Enums\BackupJobStatus;
use App\Enums\BackupJobType;
use App\Models\BackupJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class BackupService
{
    /**
     * Create a backup job.
     */
    public function createBackup(array $config, ?int $teamId = null): BackupJob
    {
        $backupJob = BackupJob::create([
            'team_id' => $teamId ?? auth()->user()?->currentTeam?->id ?? 1, // Default to team 1 for CLI
            'type' => BackupJobType::from($config['type'] ?? 'full'),
            'status' => BackupJobStatus::PENDING,
            'name' => $config['name'] ?? 'Backup ' . now()->format('Y-m-d H:i:s'),
            'description' => $config['description'] ?? null,
            'backup_config' => $config,
            'created_by' => auth()->id() ?? 1, // Default to user 1 for CLI
            'expires_at' => isset($config['retention_days'])
                ? now()->addDays($config['retention_days'])
                : now()->addDays(30),
        ]);

        // Process the backup asynchronously or synchronously based on config
        if ($config['async'] ?? true) {
            // In a real implementation, this would dispatch a job
            // dispatch(new ProcessBackupJob($backupJob));
            $this->processBackup($backupJob);
        } else {
            $this->processBackup($backupJob);
        }

        return $backupJob;
    }

    /**
     * Process a backup job.
     */
    public function processBackup(BackupJob $backupJob): bool
    {
        try {
            $backupJob->update([
                'status' => BackupJobStatus::RUNNING,
                'started_at' => now(),
            ]);

            $backupPath = $this->performBackup($backupJob);

            // Verify the backup
            $verificationResults = $this->verifyBackup($backupPath, $backupJob);

            $backupJob->update([
                'status' => BackupJobStatus::COMPLETED,
                'backup_path' => $backupPath,
                'file_size' => File::exists($backupPath) ? File::size($backupPath) : null,
                'checksum' => $this->calculateChecksum($backupPath),
                'verification_results' => $verificationResults,
                'completed_at' => now(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::channel('backups')->error('Backup job failed', [
                'backup_job_id' => $backupJob->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $backupJob->update([
                'status' => BackupJobStatus::FAILED,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return false;
        }
    }

    /**
     * Restore from a backup.
     */
    public function restore(BackupJob $backupJob, ?\Carbon\Carbon $pointInTime = null): bool
    {
        if (! $backupJob->isCompleted() || ! $backupJob->backup_path) {
            throw new \InvalidArgumentException('Backup job is not completed or backup path is missing');
        }

        if (! File::exists($backupJob->backup_path)) {
            throw new \InvalidArgumentException('Backup file does not exist');
        }

        if ($pointInTime && $pointInTime->isAfter($backupJob->completed_at)) {
            throw new \InvalidArgumentException('Cannot restore to a point in time after the backup was created');
        }

        try {
            return DB::transaction(function () use ($backupJob, $pointInTime): bool {
                // Log the restore operation
                Log::channel('backups')->info('Starting backup restore', [
                    'backup_job_id' => $backupJob->id,
                    'backup_type' => $backupJob->type->value,
                    'point_in_time' => $pointInTime?->toISOString(),
                ]);

                $result = match ($backupJob->type) {
                    BackupJobType::FULL => $this->restoreFullBackup($backupJob, $pointInTime),
                    BackupJobType::DATABASE_ONLY => $this->restoreDatabaseBackup($backupJob, $pointInTime),
                    BackupJobType::FILES_ONLY => $this->restoreFilesBackup($backupJob, $pointInTime),
                    default => throw new \InvalidArgumentException("Unsupported backup type for restore: {$backupJob->type->value}"),
                };

                if ($result) {
                    Log::channel('backups')->info('Backup restore completed successfully', [
                        'backup_job_id' => $backupJob->id,
                    ]);
                }

                return $result;
            });
        } catch (\Throwable $e) {
            Log::channel('backups')->error('Backup restore failed', [
                'backup_job_id' => $backupJob->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Verify backup integrity.
     */
    public function verifyBackup(string $backupPath, BackupJob $backupJob): array
    {
        $results = [
            'file_exists' => File::exists($backupPath),
            'file_size' => File::exists($backupPath) ? File::size($backupPath) : 0,
            'checksum_valid' => false,
            'content_valid' => false,
            'errors' => [],
        ];

        if (! $results['file_exists']) {
            $results['errors'][] = 'Backup file does not exist';

            return $results;
        }

        // Verify checksum
        $currentChecksum = $this->calculateChecksum($backupPath);
        $results['checksum_valid'] = ! in_array($currentChecksum, [null, '', '0'], true);

        // Verify content based on backup type
        try {
            $results['content_valid'] = match ($backupJob->type) {
                BackupJobType::FULL => $this->verifyFullBackup($backupPath),
                BackupJobType::DATABASE_ONLY => $this->verifyDatabaseBackup($backupPath),
                BackupJobType::FILES_ONLY => $this->verifyFilesBackup($backupPath),
                default => false,
            };
        } catch (\Throwable $e) {
            $results['errors'][] = 'Content verification failed: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Clean up expired backups.
     */
    public function cleanupExpiredBackups(): int
    {
        $expiredBackups = BackupJob::where('expires_at', '<', now())
            ->where('status', BackupJobStatus::COMPLETED)
            ->get();

        $cleanedCount = 0;

        foreach ($expiredBackups as $backup) {
            try {
                if ($backup->backup_path && File::exists($backup->backup_path)) {
                    File::delete($backup->backup_path);
                }

                $backup->update(['status' => BackupJobStatus::EXPIRED]);
                $cleanedCount++;
            } catch (\Throwable $e) {
                Log::channel('backups')->error('Failed to cleanup expired backup', [
                    'backup_job_id' => $backup->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $cleanedCount;
    }

    /**
     * Perform the actual backup based on type.
     */
    private function performBackup(BackupJob $backupJob): string
    {
        $backupDir = storage_path('app/backups');
        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$backupJob->type->value}_{$timestamp}";

        return match ($backupJob->type) {
            BackupJobType::FULL => $this->createFullBackup($backupDir, $filename, $backupJob),
            BackupJobType::DATABASE_ONLY => $this->createDatabaseBackup($backupDir, $filename, $backupJob),
            BackupJobType::FILES_ONLY => $this->createFilesBackup($backupDir, $filename, $backupJob),
            BackupJobType::INCREMENTAL => $this->createIncrementalBackup($backupDir, $filename, $backupJob),
            BackupJobType::DIFFERENTIAL => $this->createDifferentialBackup($backupDir, $filename, $backupJob),
        };
    }

    /**
     * Create a full backup (database + files).
     */
    private function createFullBackup(string $backupDir, string $filename, BackupJob $backupJob): string
    {
        $backupPath = $backupDir . '/' . $filename . '.tar.gz';
        $tempDir = $backupDir . '/temp_' . uniqid();

        try {
            File::makeDirectory($tempDir, 0755, true);

            // Create database backup
            $dbBackupPath = $this->createDatabaseDump($tempDir . '/database.sql');

            // Copy important files
            $this->copyImportantFiles($tempDir . '/files', $backupJob);

            // Create compressed archive
            $this->createTarGzArchive($tempDir, $backupPath);

            return $backupPath;
        } finally {
            // Clean up temp directory
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Create a database-only backup.
     */
    private function createDatabaseBackup(string $backupDir, string $filename, BackupJob $backupJob): string
    {
        $backupPath = $backupDir . '/' . $filename . '.sql';

        return $this->createDatabaseDump($backupPath);
    }

    /**
     * Create a files-only backup.
     */
    private function createFilesBackup(string $backupDir, string $filename, BackupJob $backupJob): string
    {
        $backupPath = $backupDir . '/' . $filename . '.tar.gz';
        $tempDir = $backupDir . '/temp_' . uniqid();

        try {
            File::makeDirectory($tempDir, 0755, true);
            $this->copyImportantFiles($tempDir, $backupJob);
            $this->createTarGzArchive($tempDir, $backupPath);

            return $backupPath;
        } finally {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Create an incremental backup.
     */
    private function createIncrementalBackup(string $backupDir, string $filename, BackupJob $backupJob): string
    {
        // Find the last full backup
        $lastFullBackup = BackupJob::where('team_id', $backupJob->team_id)
            ->where('type', BackupJobType::FULL)
            ->where('status', BackupJobStatus::COMPLETED)
            ->orderBy('completed_at', 'desc')
            ->first();

        if (! $lastFullBackup) {
            throw new \Exception('No full backup found for incremental backup');
        }

        $backupPath = $backupDir . '/' . $filename . '.tar.gz';
        $tempDir = $backupDir . '/temp_' . uniqid();

        try {
            File::makeDirectory($tempDir, 0755, true);

            // Only backup changes since last full backup
            $this->copyChangedFiles($tempDir, $lastFullBackup->completed_at, $backupJob);
            $this->createTarGzArchive($tempDir, $backupPath);

            return $backupPath;
        } finally {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Create a differential backup.
     */
    private function createDifferentialBackup(string $backupDir, string $filename, BackupJob $backupJob): string
    {
        // Similar to incremental but includes all changes since last full backup
        return $this->createIncrementalBackup($backupDir, $filename, $backupJob);
    }

    /**
     * Create database dump.
     */
    private function createDatabaseDump(string $outputPath): string
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $this->createMySQLDump($outputPath, $connection);
        } elseif ($driver === 'pgsql') {
            $this->createPostgreSQLDump($outputPath, $connection);
        } elseif ($driver === 'sqlite') {
            $this->createSQLiteDump($outputPath, $connection);
        } else {
            throw new \Exception("Unsupported database driver: {$driver}");
        }

        return $outputPath;
    }

    /**
     * Create MySQL dump.
     */
    private function createMySQLDump(string $outputPath, $connection): void
    {
        $dbConfig = $connection->getConfig();

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
            escapeshellarg((string) $dbConfig['host']),
            escapeshellarg($dbConfig['port'] ?? 3306),
            escapeshellarg((string) $dbConfig['username']),
            escapeshellarg((string) $dbConfig['password']),
            escapeshellarg((string) $dbConfig['database']),
            escapeshellarg($outputPath),
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('MySQL dump failed with return code: ' . $returnCode);
        }
    }

    /**
     * Create PostgreSQL dump.
     */
    private function createPostgreSQLDump(string $outputPath, $connection): void
    {
        $dbConfig = $connection->getConfig();

        $command = sprintf(
            'PGPASSWORD=%s pg_dump --host=%s --port=%s --username=%s --dbname=%s > %s',
            escapeshellarg((string) $dbConfig['password']),
            escapeshellarg((string) $dbConfig['host']),
            escapeshellarg($dbConfig['port'] ?? 5432),
            escapeshellarg((string) $dbConfig['username']),
            escapeshellarg((string) $dbConfig['database']),
            escapeshellarg($outputPath),
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('PostgreSQL dump failed with return code: ' . $returnCode);
        }
    }

    /**
     * Create SQLite dump.
     */
    private function createSQLiteDump(string $outputPath, $connection): void
    {
        $dbConfig = $connection->getConfig();
        $dbPath = $dbConfig['database'];

        if (! File::exists($dbPath)) {
            throw new \Exception('SQLite database file not found: ' . $dbPath);
        }

        File::copy($dbPath, $outputPath);
    }

    /**
     * Copy important files for backup.
     */
    private function copyImportantFiles(string $targetDir, BackupJob $backupJob): void
    {
        File::makeDirectory($targetDir, 0755, true);

        $config = $backupJob->backup_config;
        $filesToBackup = $config['files'] ?? [
            'storage/app',
            '.env',
            'composer.json',
            'composer.lock',
        ];

        foreach ($filesToBackup as $file) {
            $sourcePath = base_path($file);
            $targetPath = $targetDir . '/' . basename((string) $file);

            if (File::exists($sourcePath)) {
                if (File::isDirectory($sourcePath)) {
                    File::copyDirectory($sourcePath, $targetPath);
                } else {
                    File::copy($sourcePath, $targetPath);
                }
            }
        }
    }

    /**
     * Copy only changed files since a specific date.
     */
    private function copyChangedFiles(string $targetDir, \Carbon\Carbon $since, BackupJob $backupJob): void
    {
        File::makeDirectory($targetDir, 0755, true);

        $config = $backupJob->backup_config;
        $filesToCheck = $config['files'] ?? ['storage/app'];

        foreach ($filesToCheck as $file) {
            $sourcePath = base_path($file);

            if (File::exists($sourcePath)) {
                $lastModified = File::lastModified($sourcePath);

                if ($lastModified > $since->timestamp) {
                    $targetPath = $targetDir . '/' . basename((string) $file);

                    if (File::isDirectory($sourcePath)) {
                        File::copyDirectory($sourcePath, $targetPath);
                    } else {
                        File::copy($sourcePath, $targetPath);
                    }
                }
            }
        }
    }

    /**
     * Create tar.gz archive.
     */
    private function createTarGzArchive(string $sourceDir, string $targetPath): void
    {
        $command = sprintf(
            'tar -czf %s -C %s .',
            escapeshellarg($targetPath),
            escapeshellarg($sourceDir),
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Failed to create tar.gz archive with return code: ' . $returnCode);
        }
    }

    /**
     * Calculate file checksum.
     */
    private function calculateChecksum(string $filePath): ?string
    {
        if (! File::exists($filePath)) {
            return null;
        }

        return hash_file('sha256', $filePath);
    }

    /**
     * Verify full backup content.
     */
    private function verifyFullBackup(string $backupPath): bool
    {
        // Extract and verify the archive contains expected files
        $tempDir = storage_path('app/temp_verify_' . uniqid());

        try {
            File::makeDirectory($tempDir, 0755, true);

            $command = sprintf(
                'tar -xzf %s -C %s',
                escapeshellarg($backupPath),
                escapeshellarg($tempDir),
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                return false;
            }

            // Check for expected files
            $expectedFiles = ['database.sql', 'files'];

            return array_all($expectedFiles, fn ($file) => File::exists($tempDir . '/' . $file));
        } finally {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Verify database backup content.
     */
    private function verifyDatabaseBackup(string $backupPath): bool
    {
        // Check if the SQL file contains expected content
        if (! File::exists($backupPath)) {
            return false;
        }

        $content = File::get($backupPath);

        // Basic checks for SQL content
        return str_contains($content, 'CREATE TABLE') ||
               str_contains($content, 'INSERT INTO') ||
               str_contains($content, 'SQLite format');
    }

    /**
     * Verify files backup content.
     */
    private function verifyFilesBackup(string $backupPath): bool
    {
        return $this->verifyFullBackup($backupPath);
    }

    /**
     * Schedule automatic backups.
     */
    public function scheduleBackup(array $config): BackupJob
    {
        // Create a scheduled backup job
        $backupJob = BackupJob::create([
            'team_id' => $config['team_id'] ?? auth()->user()?->currentTeam?->id,
            'type' => BackupJobType::from($config['type'] ?? 'full'),
            'status' => BackupJobStatus::PENDING,
            'name' => $config['name'] ?? 'Scheduled Backup ' . now()->format('Y-m-d H:i:s'),
            'description' => $config['description'] ?? 'Automatically scheduled backup',
            'backup_config' => array_merge($config, ['scheduled' => true]),
            'created_by' => $config['created_by'] ?? auth()->id(),
            'expires_at' => isset($config['retention_days'])
                ? now()->addDays($config['retention_days'])
                : now()->addDays(30),
        ]);

        return $backupJob;
    }

    /**
     * Get point-in-time recovery options for a backup.
     */
    public function getPointInTimeRecoveryOptions(BackupJob $backupJob): array
    {
        if (! $backupJob->isCompleted()) {
            return [];
        }

        // For demonstration, return some sample recovery points
        // In a real implementation, this would analyze the backup content
        $options = [];
        $backupTime = $backupJob->completed_at;

        // Add recovery points at various intervals before the backup
        for ($i = 1; $i <= 24; $i++) {
            $recoveryPoint = $backupTime->copy()->subHours($i);
            if ($recoveryPoint->isAfter($backupJob->created_at)) {
                $options[] = [
                    'timestamp' => $recoveryPoint,
                    'label' => $recoveryPoint->format('Y-m-d H:i:s') . ' (' . $recoveryPoint->diffForHumans() . ')',
                    'available' => true,
                ];
            }
        }

        return $options;
    }

    /**
     * Restore full backup.
     */
    private function restoreFullBackup(BackupJob $backupJob, ?\Carbon\Carbon $pointInTime = null): bool
    {
        try {
            $tempDir = storage_path('app/temp_restore_' . uniqid());
            File::makeDirectory($tempDir, 0755, true);

            // Extract the backup
            $command = sprintf(
                'tar -xzf %s -C %s',
                escapeshellarg((string) $backupJob->backup_path),
                escapeshellarg($tempDir),
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Failed to extract backup archive');
            }

            // Restore database
            $dbRestoreResult = $this->restoreDatabaseFromPath($tempDir . '/database.sql', $pointInTime);

            // Restore files
            $filesRestoreResult = $this->restoreFilesFromPath($tempDir . '/files', $pointInTime);

            // Clean up temp directory
            File::deleteDirectory($tempDir);

            return $dbRestoreResult && $filesRestoreResult;
        } catch (\Throwable $e) {
            Log::channel('backups')->error('Full backup restore failed', [
                'backup_job_id' => $backupJob->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Restore database backup.
     */
    private function restoreDatabaseBackup(BackupJob $backupJob, ?\Carbon\Carbon $pointInTime = null): bool
    {
        return $this->restoreDatabaseFromPath($backupJob->backup_path, $pointInTime);
    }

    /**
     * Restore files backup.
     */
    private function restoreFilesBackup(BackupJob $backupJob, ?\Carbon\Carbon $pointInTime = null): bool
    {
        try {
            $tempDir = storage_path('app/temp_restore_' . uniqid());
            File::makeDirectory($tempDir, 0755, true);

            // Extract the backup
            $command = sprintf(
                'tar -xzf %s -C %s',
                escapeshellarg((string) $backupJob->backup_path),
                escapeshellarg($tempDir),
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Failed to extract files backup archive');
            }

            $result = $this->restoreFilesFromPath($tempDir, $pointInTime);

            // Clean up temp directory
            File::deleteDirectory($tempDir);

            return $result;
        } catch (\Throwable $e) {
            Log::channel('backups')->error('Files backup restore failed', [
                'backup_job_id' => $backupJob->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Restore database from a SQL file path.
     */
    private function restoreDatabaseFromPath(string $sqlPath, ?\Carbon\Carbon $pointInTime = null): bool
    {
        if (! File::exists($sqlPath)) {
            Log::channel('backups')->error('Database backup file not found', ['path' => $sqlPath]);

            return false;
        }

        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();

            if ($pointInTime instanceof \Carbon\Carbon) {
                Log::channel('backups')->info('Point-in-time database restore requested', [
                    'target_time' => $pointInTime->toISOString(),
                ]);
                // For point-in-time recovery, we would need to apply transaction logs
                // This is a simplified implementation
            }
            if ($driver === 'mysql') {
                return $this->restoreMySQLDatabase($sqlPath);
            }
            if ($driver === 'pgsql') {
                return $this->restorePostgreSQLDatabase($sqlPath);
            }

            if ($driver === 'sqlite') {
                return $this->restoreSQLiteDatabase($sqlPath);
            }

            throw new \Exception("Unsupported database driver for restore: {$driver}");
        } catch (\Throwable $e) {
            Log::channel('backups')->error('Database restore failed', [
                'sql_path' => $sqlPath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Restore files from a directory path.
     */
    private function restoreFilesFromPath(string $filesPath, ?\Carbon\Carbon $pointInTime = null): bool
    {
        if (! File::exists($filesPath)) {
            Log::channel('backups')->error('Files backup directory not found', ['path' => $filesPath]);

            return false;
        }

        try {
            if ($pointInTime instanceof \Carbon\Carbon) {
                Log::channel('backups')->info('Point-in-time files restore requested', [
                    'target_time' => $pointInTime->toISOString(),
                ]);
                // For point-in-time recovery, we would filter files by modification time
            }

            // Restore storage/app directory
            $storageSource = $filesPath . '/storage/app';
            $storageTarget = storage_path('app');

            if (File::exists($storageSource)) {
                // Backup current storage before restore
                $backupStorage = storage_path('app_backup_' . now()->format('Y_m_d_H_i_s'));
                File::copyDirectory($storageTarget, $backupStorage);

                // Restore from backup
                File::deleteDirectory($storageTarget);
                File::copyDirectory($storageSource, $storageTarget);

                Log::channel('backups')->info('Storage directory restored', [
                    'backup_location' => $backupStorage,
                ]);
            }

            // Restore .env file if it exists in backup
            $envSource = $filesPath . '/.env';
            if (File::exists($envSource)) {
                $envTarget = base_path('.env');
                $envBackup = base_path('.env.backup.' . now()->format('Y_m_d_H_i_s'));

                // Backup current .env
                File::copy($envTarget, $envBackup);

                // Restore .env from backup
                File::copy($envSource, $envTarget);

                Log::channel('backups')->info('Environment file restored', [
                    'backup_location' => $envBackup,
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            Log::channel('backups')->error('Files restore failed', [
                'files_path' => $filesPath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Restore MySQL database.
     */
    private function restoreMySQLDatabase(string $sqlPath): bool
    {
        $dbConfig = DB::connection()->getConfig();

        $command = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
            escapeshellarg((string) $dbConfig['host']),
            escapeshellarg($dbConfig['port'] ?? 3306),
            escapeshellarg((string) $dbConfig['username']),
            escapeshellarg((string) $dbConfig['password']),
            escapeshellarg((string) $dbConfig['database']),
            escapeshellarg($sqlPath),
        );

        exec($command, $output, $returnCode);

        return $returnCode === 0;
    }

    /**
     * Restore PostgreSQL database.
     */
    private function restorePostgreSQLDatabase(string $sqlPath): bool
    {
        $dbConfig = DB::connection()->getConfig();

        $command = sprintf(
            'PGPASSWORD=%s psql --host=%s --port=%s --username=%s --dbname=%s < %s',
            escapeshellarg((string) $dbConfig['password']),
            escapeshellarg((string) $dbConfig['host']),
            escapeshellarg($dbConfig['port'] ?? 5432),
            escapeshellarg((string) $dbConfig['username']),
            escapeshellarg((string) $dbConfig['database']),
            escapeshellarg($sqlPath),
        );

        exec($command, $output, $returnCode);

        return $returnCode === 0;
    }

    /**
     * Restore SQLite database.
     */
    private function restoreSQLiteDatabase(string $sqlPath): bool
    {
        $dbConfig = DB::connection()->getConfig();
        $dbPath = $dbConfig['database'];

        // Backup current database
        $backupPath = $dbPath . '.backup.' . now()->format('Y_m_d_H_i_s');
        File::copy($dbPath, $backupPath);

        // Restore from backup
        File::copy($sqlPath, $dbPath);

        Log::channel('backups')->info('SQLite database restored', [
            'backup_location' => $backupPath,
        ]);

        return true;
    }
}
