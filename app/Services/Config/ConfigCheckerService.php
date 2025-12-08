<?php

declare(strict_types=1);

namespace App\Services\Config;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

final readonly class ConfigCheckerService
{
    public function __construct(
        private int $cacheTtl = 300
    ) {}

    /**
     * Run the config check and return results.
     * Note: Since the package outputs to console, we capture it.
     */
    public function check(): array
    {
        // run the artisan command and capture output
        // The package might not return structured data, so we might need to parse the text output
        // or just return the text.

        // Using Artisan::call preserves output in a buffer we can retrieve
        Artisan::call('config:check');
        $output = Artisan::output();

        return $this->parseOutput($output);
    }

    /**
     * Get cached check results.
     */
    public function getCachedCheck(): array
    {
        return Cache::remember('config_checker.last_run', $this->cacheTtl, fn (): array => $this->check());
    }

    /**
     * Clear the cache.
     */
    public function clearCache(): void
    {
        Cache::forget('config_checker.last_run');
    }

    /**
     * Parse the raw command output into a structured format.
     */
    private function parseOutput(string $output): array
    {
        $lines = explode("\n", $output);
        $issues = [];
        $status = 'healthy';
        $inTable = false;

        foreach ($lines as $line) {
            $line = trim($line);

            // Detect table start (headers or separator)
            if (str_contains($line, 'File') && str_contains($line, 'Key Referenced')) {
                $inTable = true;

                continue;
            }
            // Skip table separators
            if (str_starts_with($line, '┌')) {
                continue;
            }
            if (str_starts_with($line, '├')) {
                continue;
            }
            if (str_starts_with($line, '└')) {
                continue;
            }
            if (str_starts_with($line, '─')) {
                continue;
            }

            if ($inTable && ($line !== '' && $line !== '0')) {
                // Remove leading/trailing pipes
                $content = trim($line, '|');

                // Split by pipe
                $parts = array_map(trim(...), explode('|', $content));

                if (count($parts) >= 3) {
                    $status = 'issues_found';
                    $issues[] = [
                        'file' => $parts[0] ?? 'Unknown',
                        'line' => $parts[1] ?? '0',
                        'key' => $parts[2] ?? 'Unknown',
                        'method' => $parts[3] ?? 'config()',
                    ];
                }
            }
        }

        return [
            'status' => $status,
            'raw_output' => $output,
            'issues' => $issues,
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
