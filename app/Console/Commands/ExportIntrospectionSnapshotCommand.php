<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AI\CodebaseIntrospectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class ExportIntrospectionSnapshotCommand extends Command
{
    protected $signature = 'introspect:export {--path=} {--fresh}';

    protected $description = 'Export codebase metadata with laravel-introspect';

    public function __construct(
        private readonly CodebaseIntrospectionService $introspection,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $forceRefresh = (bool) $this->option('fresh');
        $outputPath = $this->option('path') ?: config('introspect.export.path', storage_path('app/introspection/snapshot.json'));

        $snapshot = $this->introspection->snapshot($forceRefresh);

        File::ensureDirectoryExists(dirname((string) $outputPath));
        File::put(
            $outputPath,
            json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        $this->info("Introspection snapshot written to {$outputPath}");

        return self::SUCCESS;
    }
}
