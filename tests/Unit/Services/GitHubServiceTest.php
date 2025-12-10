<?php

declare(strict_types=1);

use App\Services\GitHubService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    // Create a new instance of the service for each test
    $this->service = new GitHubService;

    // Clear any cached values before each test
    $testOwner = 'TestOwner';
    $testRepo = 'testrepo';
    $testBrand = 'Test CRM';
    config([
        'laravel-crm.ui.github_owner' => $testOwner,
        'laravel-crm.ui.github_repo' => $testRepo,
        'laravel-crm.ui.brand_name' => $testBrand,
        'app.url' => 'https://crm.test',
    ]);

    Cache::forget("github_stars_{$testOwner}_{$testRepo}");
});

it('gets stars count from GitHub API', function (): void {
    $testOwner = config('laravel-crm.ui.github_owner', 'TestOwner');
    $testRepo = config('laravel-crm.ui.github_repo', 'testrepo');
    $testBrand = brand_name();

    // Mock HTTP response
    Http::fake([
        "api.github.com/repos/{$testOwner}/{$testRepo}" => Http::response([
            'stargazers_count' => 125,
        ], 200),
    ]);

    // Call the service
    $result = $this->service->getStarsCount($testOwner, $testRepo);

    // Assert the result
    expect($result)->toBe(125);

    // Verify the HTTP request was made
    Http::assertSent(fn ($request): bool => $request->url() === "https://api.github.com/repos/{$testOwner}/{$testRepo}"
        && $request->hasHeader('Accept', 'application/vnd.github+json')
        && $request->hasHeader('User-Agent', "{$testBrand} HTTP Client (https://crm.test)"));
});

it('uses cached stars count on subsequent calls', function (): void {
    $testOwner = config('laravel-crm.ui.github_owner', 'TestOwner');
    $testRepo = config('laravel-crm.ui.github_repo', 'testrepo');

    // Mock HTTP response
    Http::fake([
        "api.github.com/repos/{$testOwner}/{$testRepo}" => Http::response([
            'stargazers_count' => 125,
        ], 200),
    ]);

    // First call should hit the API
    $firstResult = $this->service->getStarsCount($testOwner, $testRepo);
    expect($firstResult)->toBe(125);

    // Verify the API was called
    Http::assertSentCount(1);

    // Second call should use the cache and not hit the API again
    $secondResult = $this->service->getStarsCount($testOwner, $testRepo);
    expect($secondResult)->toBe(125);

    // Still only 1 call total
    Http::assertSentCount(1);
});

it('returns 0 when API call fails', function (): void {
    $testOwner = config('laravel-crm.ui.github_owner', 'TestOwner');
    $testRepo = config('laravel-crm.ui.github_repo', 'testrepo');

    // Mock HTTP failure response
    Http::fake([
        "api.github.com/repos/{$testOwner}/{$testRepo}" => Http::response(null, 500),
    ]);

    // Call the service
    $result = $this->service->getStarsCount();

    // Should return 0 on failure
    expect($result)->toBe(0);
});

it('returns 0 when API throws exception', function (): void {
    // Mock HTTP exception
    Http::fake(function (): void {
        throw new \Exception('Network error');
    });

    // Call the service
    $result = $this->service->getStarsCount();

    // Should return 0 on exception
    expect($result)->toBe(0);
});

it('formats small numbers as plain numbers', function (): void {
    $testOwner = config('laravel-crm.ui.github_owner', 'TestOwner');
    $testRepo = config('laravel-crm.ui.github_repo', 'testrepo');

    // Mock HTTP response
    Http::fake([
        "api.github.com/repos/{$testOwner}/{$testRepo}" => Http::response([
            'stargazers_count' => 42,
        ], 200),
    ]);

    // Call the service
    $result = $this->service->getFormattedStarsCount($testOwner, $testRepo);

    // For small numbers, should return as is
    expect($result)->toBe('42');
});

it('uses abbreviation for large star counts', function (): void {
    $testOwner = config('laravel-crm.ui.github_owner', 'TestOwner');
    $testRepo = config('laravel-crm.ui.github_repo', 'testrepo');

    // Mock HTTP response with large value
    Http::fake([
        "api.github.com/repos/{$testOwner}/{$testRepo}" => Http::response([
            'stargazers_count' => 12500,
        ], 200),
    ]);

    // Call the service
    $result = $this->service->getFormattedStarsCount($testOwner, $testRepo);

    // Should be abbreviated
    expect($result)->toBe('12.5K');
});