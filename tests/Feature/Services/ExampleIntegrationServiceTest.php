<?php

declare(strict_types=1);

use App\Services\Example\ExampleIntegrationService;
use Illuminate\Support\Facades\Http;

/**
 * Feature tests for ExampleIntegrationService demonstrating HTTP testing patterns.
 *
 * Feature tests use Http::fake() to test external API interactions.
 */
it('fetches data from external API successfully', function (): void {
    Http::fake([
        'https://api.example.com/data' => Http::response([
            'status' => 'success',
            'data' => ['key' => 'value'],
        ], 200),
    ]);

    $service = new ExampleIntegrationService(
        apiKey: 'test-key',
        timeout: 10,
    );

    $result = $service->fetchData('https://api.example.com/data');

    expect($result)->toBeArray();
    expect($result['status'])->toBe('success');
    expect($result['data']['key'])->toBe('value');

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer test-key')
        && $request->url() === 'https://api.example.com/data');
});

it('handles API failures gracefully', function (): void {
    Http::fake([
        'https://api.example.com/data' => Http::response([], 500),
    ]);

    $service = new ExampleIntegrationService(
        apiKey: 'test-key',
        timeout: 10,
    );

    $result = $service->fetchData('https://api.example.com/data');

    expect($result)->toBeNull();
});

it('retries failed requests', function (): void {
    Http::fake([
        'https://api.example.com/data' => Http::sequence()
            ->push([], 500)
            ->push([], 500)
            ->push(['status' => 'success'], 200),
    ]);

    $service = new ExampleIntegrationService(
        apiKey: 'test-key',
        timeout: 10,
        retries: 3,
    );

    $result = $service->fetchData('https://api.example.com/data');

    expect($result)->toBeArray();
    expect($result['status'])->toBe('success');

    // Should have made 3 attempts
    Http::assertSentCount(3);
});

it('posts data to external API', function (): void {
    Http::fake([
        'https://api.example.com/submit' => Http::response(['id' => 123], 201),
    ]);

    $service = new ExampleIntegrationService(
        apiKey: 'test-key',
        timeout: 10,
    );

    $result = $service->postData('https://api.example.com/submit', [
        'name' => 'Test',
        'email' => 'test@example.com',
    ]);

    expect($result)->toBeTrue();

    Http::assertSent(fn (array $request): bool => $request->method() === 'POST'
        && $request->hasHeader('Authorization', 'Bearer test-key')
        && $request['name'] === 'Test');
});
