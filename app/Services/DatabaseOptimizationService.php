<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class DatabaseOptimizationService
{
    public function __construct(
        private Filesystem $filesystem,
    ) {}

    /**
     * @return array{
     *     driver: string,
     *     sqlite_version: string|null,
     *     migration_path: string|null,
     *     migration_applied: bool|null,
     *     pragmas: array<string, array{expected: string, actual: string|null}>
     * }
     */
    public function status(): array
    {
        $migrationPath = $this->getMigrationPath();

        return [
            'driver' => DB::getDriverName(),
            'sqlite_version' => $this->getSqliteVersion(),
            'migration_path' => $migrationPath,
            'migration_applied' => $migrationPath ? $this->isMigrationApplied($migrationPath) : null,
            'pragmas' => $this->runtimePragmas(),
        ];
    }

    public function ensureMigrationPublished(): ?string
    {
        $existing = $this->getMigrationPath();

        if ($existing !== null) {
            return $existing;
        }

        Artisan::call('db:optimize');

        return $this->getMigrationPath();
    }

    /**
     * @return array<string, array{expected: string, actual: string|null}>
     */
    public function runtimePragmas(): array
    {
        if (DB::getDriverName() !== 'sqlite') {
            return [];
        }

        return collect([
            'auto_vacuum' => 'incremental',
            'journal_mode' => 'wal',
            'page_size' => '32768',
            'busy_timeout' => '5000',
            'cache_size' => '-20000',
            'foreign_keys' => 'on',
            'mmap_size' => '2147483648',
            'temp_store' => 'memory',
            'synchronous' => 'normal',
        ])->map(function (string $expected, string $pragma): array {
            $actual = $this->readPragma($pragma);

            return [
                'expected' => $expected,
                'actual' => $actual,
            ];
        })->all();
    }

    private function getSqliteVersion(): ?string
    {
        if (DB::getDriverName() !== 'sqlite') {
            return null;
        }

        try {
            $result = DB::select('select sqlite_version() as version');

            return isset($result[0]) ? (string) ($result[0]->version ?? null) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function readPragma(string $pragma): ?string
    {
        try {
            $result = DB::select("PRAGMA {$pragma};");

            if ($result === []) {
                return null;
            }

            $value = (array) $result[0];
            $firstValue = array_shift($value);

            return $firstValue === null ? null : strtolower((string) $firstValue);
        } catch (Throwable) {
            return null;
        }
    }

    private function getMigrationPath(): ?string
    {
        $matches = $this->filesystem->glob(database_path('migrations/*_optimize_database_settings.php'));

        if ($matches === false || $matches === []) {
            return null;
        }

        sort($matches);

        return end($matches) ?: null;
    }

    private function isMigrationApplied(string $path): ?bool
    {
        try {
            $migration = pathinfo($path, PATHINFO_FILENAME);

            return DB::table('migrations')
                ->where('migration', $migration)
                ->exists();
        } catch (Throwable) {
            return null;
        }
    }
}
