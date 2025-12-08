<?php

declare(strict_types=1);

namespace App\Services;

use JsonException;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class PhpInsightsService
{
    /**
     * @return array{summary: array<string, mixed>, issues: array<string, array<int, array<string, mixed>>>}
     *
     * @throws ProcessFailedException
     * @throws JsonException
     */
    public function analyze(int $limit = 15): array
    {
        $process = Process::fromShellCommandline(
            'php -d memory_limit=1G ./vendor/bin/phpinsights --ansi --format=json --quiet --no-interaction',
            base_path(),
        );

        $process->setTimeout((float) max(config('insights.timeout', 180), 120));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $report = $this->decodeReport($process->getOutput());

        return [
            'summary' => $report['summary'] ?? [],
            'issues' => [
                'code' => array_slice($report['Code'] ?? [], 0, $limit),
                'complexity' => array_slice($report['Complexity'] ?? [], 0, $limit),
                'architecture' => array_slice($report['Architecture'] ?? [], 0, $limit),
                'style' => array_slice($report['Style'] ?? [], 0, $limit),
                'security' => array_slice($report['Security'] ?? [], 0, $limit),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function decodeReport(string $output): array
    {
        $jsonStart = strrpos($output, '{');

        if ($jsonStart === false) {
            throw new RuntimeException('Unable to parse PhpInsights output.');
        }

        $json = substr($output, $jsonStart);

        return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }
}
