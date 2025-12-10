<?php

declare(strict_types=1);

use App\Services\Testing\CodeCoverageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

uses()->group('unit', 'services', 'coverage');

beforeEach(function (): void {
    $this->service = new CodeCoverageService(
        coverageDir: 'coverage-html',
        cloverFile: 'coverage.xml',
        cacheTtl: 300,
    );
});

it('returns default stats when no coverage file exists', function (): void {
    File::shouldReceive('exists')
        ->with(base_path('coverage.xml'))
        ->andReturn(false);

    Cache::shouldReceive('remember')
        ->once()
        ->andReturnUsing(fn ($key, $ttl, $callback) => $callback());

    $stats = $this->service->getCoverageStats();

    expect($stats)->toBeArray()
        ->and($stats['overall'])->toBe(0)
        ->and($stats['lines'])->toBe(0)
        ->and($stats['methods'])->toBe(0)
        ->and($stats['classes'])->toBe(0);
});

it('checks if pcov is enabled', function (): void {
    $enabled = $this->service->isPcovEnabled();

    expect($enabled)->toBeBool();
});

it('gets pcov configuration when enabled', function (): void {
    if (! extension_loaded('pcov')) {
        $this->markTestSkipped('PCOV extension not loaded');
    }

    $config = $this->service->getPcovConfig();

    expect($config)->toBeArray()
        ->and($config)->toHaveKeys(['enabled', 'directory', 'exclude']);
});

it('returns empty config when pcov is disabled', function (): void {
    if (extension_loaded('pcov')) {
        $this->markTestSkipped('PCOV extension is loaded');
    }

    $config = $this->service->getPcovConfig();

    expect($config)->toBeArray()
        ->and($config)->toBeEmpty();
});

it('checks coverage threshold', function (): void {
    expect($this->service->meetsThreshold(85.5, 80.0))->toBeTrue()
        ->and($this->service->meetsThreshold(75.0, 80.0))->toBeFalse()
        ->and($this->service->meetsThreshold(80.0, 80.0))->toBeTrue();
});

it('gets coverage trend', function (): void {
    Cache::shouldReceive('remember')
        ->andReturn(collect([
            ['date' => '2024-01-01', 'coverage' => 75.0],
            ['date' => '2024-01-02', 'coverage' => 76.0],
            ['date' => '2024-01-03', 'coverage' => 77.0],
            ['date' => '2024-01-04', 'coverage' => 78.0],
            ['date' => '2024-01-05', 'coverage' => 79.0],
            ['date' => '2024-01-06', 'coverage' => 80.0],
            ['date' => '2024-01-07', 'coverage' => 82.0],
        ]));

    $trend = $this->service->getCoverageTrend();

    expect($trend)->toBeIn(['up', 'down', 'stable']);
});

it('gets coverage history', function (): void {
    Cache::shouldReceive('remember')
        ->once()
        ->andReturnUsing(fn ($key, $ttl, $callback) => collect(range(1, 7))->map(fn ($day): array => [
            'date' => now()->subDays($day)->format('Y-m-d'),
            'coverage' => random_int(75, 85),
            'lines' => random_int(1000, 1500),
            'methods' => random_int(200, 300),
        ])->reverse()->values());

    $history = $this->service->getCoverageHistory(7);

    expect($history)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($history)->toHaveCount(7)
        ->and($history->first())->toHaveKeys(['date', 'coverage', 'lines', 'methods']);
});

it('clears coverage cache', function (): void {
    Cache::shouldReceive('forget')
        ->once()
        ->with('coverage.stats');

    Cache::shouldReceive('tags')
        ->once()
        ->with(['coverage'])
        ->andReturnSelf();

    Cache::shouldReceive('flush')
        ->once();

    $this->service->clearCache();

    expect(true)->toBeTrue();
});

it('gets coverage by category', function (): void {
    File::shouldReceive('exists')
        ->with(base_path('coverage.xml'))
        ->andReturn(false);

    Cache::shouldReceive('remember')
        ->andReturnUsing(fn ($key, $ttl, $callback) => $callback());

    $categories = $this->service->getCoverageByCategory();

    expect($categories)->toBeArray();
});

it('parses coverage report when file does not exist', function (): void {
    File::shouldReceive('exists')
        ->with('/nonexistent/path.xml')
        ->andReturn(false);

    $result = $this->service->parseCoverageReport('/nonexistent/path.xml');

    expect($result)->toBeArray()
        ->and($result)->toBeEmpty();
});