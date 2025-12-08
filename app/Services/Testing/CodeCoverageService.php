<?php

declare(strict_types=1);

namespace App\Services\Testing;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Code Coverage Service
 *
 * Provides programmatic access to code coverage data and analysis.
 * Integrates with PCOV for fast coverage generation.
 */
final readonly class CodeCoverageService
{
    public function __construct(
        private string $coverageDir = 'coverage-html',
        private string $cloverFile = 'coverage.xml',
        private int $cacheTtl = 300
    ) {}

    /**
     * Get current coverage statistics
     */
    public function getCoverageStats(): array
    {
        return Cache::remember('coverage.stats', $this->cacheTtl, function (): array {
            if (! File::exists(base_path($this->cloverFile))) {
                return $this->getDefaultStats();
            }

            try {
                $xml = simplexml_load_file(base_path($this->cloverFile));
                $metrics = $xml->project->metrics;

                $totalStatements = (int) $metrics['statements'];
                $coveredStatements = (int) $metrics['coveredstatements'];
                $totalMethods = (int) $metrics['methods'];
                $coveredMethods = (int) $metrics['coveredmethods'];
                $totalClasses = (int) $metrics['classes'];
                $coveredClasses = (int) $metrics['coveredclasses'];

                $linesCoverage = $totalStatements > 0
                    ? round(($coveredStatements / $totalStatements) * 100, 2)
                    : 0;

                $methodsCoverage = $totalMethods > 0
                    ? round(($coveredMethods / $totalMethods) * 100, 2)
                    : 0;

                $classesCoverage = $totalClasses > 0
                    ? round(($coveredClasses / $totalClasses) * 100, 2)
                    : 0;

                return [
                    'overall' => $linesCoverage,
                    'lines' => $linesCoverage,
                    'methods' => $methodsCoverage,
                    'classes' => $classesCoverage,
                    'total_statements' => $totalStatements,
                    'covered_statements' => $coveredStatements,
                    'total_methods' => $totalMethods,
                    'covered_methods' => $coveredMethods,
                    'total_classes' => $totalClasses,
                    'covered_classes' => $coveredClasses,
                    'generated_at' => File::lastModified(base_path($this->cloverFile)),
                ];
            } catch (\Exception $e) {
                Log::error('Failed to parse coverage report', [
                    'error' => $e->getMessage(),
                    'file' => $this->cloverFile,
                ]);

                return $this->getDefaultStats();
            }
        });
    }

    /**
     * Run coverage analysis
     */
    public function runCoverage(?string $suite = null, bool $html = true): array
    {
        $command = ['vendor/bin/pest', '--coverage'];

        if ($html) {
            $command[] = '--coverage-html='.$this->coverageDir;
        }

        $command[] = '--coverage-clover='.$this->cloverFile;

        if ($suite) {
            $command[] = '--testsuite='.$suite;
        }

        try {
            $result = Process::path(base_path())
                ->timeout(300)
                ->run(implode(' ', $command));

            $this->clearCache();

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
                'error' => $result->errorOutput(),
                'exit_code' => $result->exitCode(),
            ];
        } catch (\Exception $e) {
            Log::error('Coverage analysis failed', [
                'error' => $e->getMessage(),
                'command' => implode(' ', $command),
            ]);

            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
                'exit_code' => 1,
            ];
        }
    }

    /**
     * Get coverage history
     */
    public function getCoverageHistory(int $days = 30): Collection
    {
        return Cache::remember("coverage.history.{$days}", 3600, fn () =>
            // In a real implementation, this would query a database table
            // For now, return mock data
            collect(range(1, $days))->map(fn ($day): array => [
                'date' => now()->subDays($day)->format('Y-m-d'),
                'coverage' => random_int(75, 85) + (random_int(0, 99) / 100),
                'lines' => random_int(1000, 1500),
                'methods' => random_int(200, 300),
            ])->reverse()->values());
    }

    /**
     * Parse coverage report
     */
    public function parseCoverageReport(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }

        try {
            $xml = simplexml_load_file($path);
            $files = [];

            foreach ($xml->project->file as $file) {
                $fileName = (string) $file['name'];
                $lines = [];

                foreach ($file->line as $line) {
                    $lines[(int) $line['num']] = [
                        'type' => (string) $line['type'],
                        'count' => (int) $line['count'],
                    ];
                }

                $files[$fileName] = $lines;
            }

            return $files;
        } catch (\Exception $e) {
            Log::error('Failed to parse coverage report', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);

            return [];
        }
    }

    /**
     * Check if coverage meets threshold
     */
    public function meetsThreshold(float $coverage, float $threshold = 80.0): bool
    {
        return $coverage >= $threshold;
    }

    /**
     * Get coverage trend
     */
    public function getCoverageTrend(): string
    {
        $history = $this->getCoverageHistory(7);

        if ($history->count() < 2) {
            return 'stable';
        }

        $recent = $history->take(3)->avg('coverage');
        $previous = $history->skip(3)->take(3)->avg('coverage');

        $diff = $recent - $previous;

        if ($diff > 1) {
            return 'up';
        }

        if ($diff < -1) {
            return 'down';
        }

        return 'stable';
    }

    /**
     * Get coverage by category
     */
    public function getCoverageByCategory(): array
    {
        $stats = $this->getCoverageStats();

        if ($stats['overall'] === 0) {
            return [];
        }

        // Parse coverage report to get per-directory stats
        $files = $this->parseCoverageReport(base_path($this->cloverFile));

        $categories = [
            'Models' => 0,
            'Services' => 0,
            'Controllers' => 0,
            'Resources' => 0,
            'Other' => 0,
        ];

        foreach ($files as $file => $lines) {
            $category = $this->categorizeFile($file);
            // Simplified calculation - in reality would need proper metrics
            $categories[$category] += count(array_filter($lines, fn (array $line): bool => $line['count'] > 0));
        }

        return $categories;
    }

    /**
     * Clear coverage cache
     */
    public function clearCache(): void
    {
        Cache::forget('coverage.stats');
        Cache::tags(['coverage'])->flush();
    }

    /**
     * Check if PCOV is enabled
     */
    public function isPcovEnabled(): bool
    {
        return extension_loaded('pcov') && ini_get('pcov.enabled') === '1';
    }

    /**
     * Get PCOV configuration
     */
    public function getPcovConfig(): array
    {
        if (! $this->isPcovEnabled()) {
            return [];
        }

        return [
            'enabled' => ini_get('pcov.enabled'),
            'directory' => ini_get('pcov.directory'),
            'exclude' => ini_get('pcov.exclude'),
        ];
    }

    /**
     * Get default stats when no coverage data exists
     */
    private function getDefaultStats(): array
    {
        return [
            'overall' => 0,
            'lines' => 0,
            'methods' => 0,
            'classes' => 0,
            'total_statements' => 0,
            'covered_statements' => 0,
            'total_methods' => 0,
            'covered_methods' => 0,
            'total_classes' => 0,
            'covered_classes' => 0,
            'generated_at' => null,
        ];
    }

    /**
     * Categorize file by path
     */
    private function categorizeFile(string $file): string
    {
        if (str_contains($file, '/Models/')) {
            return 'Models';
        }

        if (str_contains($file, '/Services/')) {
            return 'Services';
        }

        if (str_contains($file, '/Controllers/') || str_contains($file, '/Http/')) {
            return 'Controllers';
        }

        if (str_contains($file, '/Resources/') || str_contains($file, '/Filament/')) {
            return 'Resources';
        }

        return 'Other';
    }
}
