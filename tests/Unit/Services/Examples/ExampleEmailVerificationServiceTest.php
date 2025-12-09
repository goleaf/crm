<?php

declare(strict_types=1);

use App\Services\Examples\ExampleEmailVerificationService;
use Illuminate\Support\Facades\Http;

/**
 * Example Email Verification Service Tests
 *
 * These tests demonstrate best practices for testing services:
 * - Using Http::fake() for external API calls
 * - Testing success and failure scenarios
 * - Testing error handling
 * - Using descriptive test names
 * - Testing DTOs and helper methods
 */
beforeEach(function (): void {
    // Prevent any real HTTP requests during tests
    Http::preventStrayRequests();
});

it('verifies valid email addresses', function (): void {
    Http::fake([
        'api.emailverification.com/*' => Http::response([
            'valid' => true,
            'disposable' => false,
        ], 200),
    ]);

    $service = new ExampleEmailVerificationService(
        apiKey: 'test-key',
        apiUrl: 'https://api.emailverification.com',
        timeout: 10,
    );

    $result = $service->verify('john@example.com');

    expect($result->isValid)->toBeTrue();
    expect($result->isDisposable)->toBeFalse();
    expect($result->error)->toBeNull();
    expect($result->isSuccessful())->toBeTrue();
    expect($result->isAcceptable())->toBeTrue();
});

it('detects disposable email addresses', function (): void {
    Http::fake([
        'api.emailverification.com/*' => Http::response([
            'valid' => true,
            'disposable' => true,
        ], 200),
    ]);

    $service = new ExampleEmailVerificationService(
        apiKey: 'test-key',
        apiUrl: 'https://api.emailverification.com',
        timeout: 10,
    );

    $result = $service->verify('temp@tempmail.com');

    expect($result->isValid)->toBeTrue();
    expect($result->isDisposable)->toBeTrue();
    expect($result->isSuccessful())->toBeTrue();
    expect($result->isAcceptable())->toBeFalse(); // Not acceptable due to disposable
});

it('detects invalid email addresses', function (): void {
    Http::fake([
        'api.emailverification.com/*' => Http::response([
            'valid' => false,
            'disposable' => false,
        ], 200),
    ]);

    $service = new ExampleEmailVerificationService(
        apiKey: 'test-key',
        apiUrl: 'https://api.emailverification.com',
        timeout: 10,
    );

    $result = $service->verify('invalid-email');

    expect($result->isValid)->toBeFalse();
    expect($result->isDisposable)->toBeFalse();
    expect($result->isSuccessful())->toBeTrue();
    expect($result->isAcceptable())->toBeFalse();
});

it('handles API failures gracefully', function (): void {
    Http::fake([
        'api.emailverification.com/*' => Http::response([], 500),
    ]);

    $service = new ExampleEmailVerificationService(
        apiKey: 'test-key',
        apiUrl: 'https://api.emailverification.com',
        timeout: 10,
    );

    $result = $service->verify('john@example.com');

    expect($result->isValid)->toBeFalse();
    expect($result->error)->toBe('API request failed');
    expect($result->isSuccessful())->toBeFalse();
    expect($result->isAcceptable())->toBeFalse();
});

it('handles network exceptions gracefully', function (): void {
    Http::fake([
        'api.emailverification.com/*' => function (): void {
            throw new \Exception('Network error');
        },
    ]);

    $service = new ExampleEmailVerificationService(
        apiKey: 'test-key',
        apiUrl: 'https://api.emailverification.com',
        timeout: 10,
    );

    $result = $service->verify('john@example.com');

    expect($result->isValid)->toBeFalse();
    expect($result->error)->toBe('Network error');
    expect($result->isSuccessful())->toBeFalse();
});

it('verifies multiple emails in batch', function (): void {
    Http::fake([
        'api.emailverification.com/*' => Http::sequence()
            ->push(['valid' => true, 'disposable' => false], 200)
            ->push(['valid' => true, 'disposable' => true], 200)
            ->push(['valid' => false, 'disposable' => false], 200),
    ]);

    $service = new ExampleEmailVerificationService(
        apiKey: 'test-key',
        apiUrl: 'https://api.emailverification.com',
        timeout: 10,
    );

    $results = $service->verifyBatch([
        'john@example.com',
        'temp@tempmail.com',
        'invalid-email',
    ]);

    expect($results)->toHaveCount(3);
    expect($results['john@example.com']->isAcceptable())->toBeTrue();
    expect($results['temp@tempmail.com']->isAcceptable())->toBeFalse();
    expect($results['invalid-email']->isAcceptable())->toBeFalse();
});

it('can be created from configuration', function (): void {
    config([
        'services.email_verification.api_key' => 'config-key',
        'services.email_verification.api_url' => 'https://api.config.com',
        'services.email_verification.timeout' => 15,
    ]);

    Http::fake([
        'api.config.com/*' => Http::response([
            'valid' => true,
            'disposable' => false,
        ], 200),
    ]);

    $service = ExampleEmailVerificationService::fromConfig();
    $result = $service->verify('test@example.com');

    expect($result->isValid)->toBeTrue();
    Http::assertSent(fn ($request): bool => $request->hasHeader('X-API-Key', 'config-key')
        && str_contains((string) $request->url(), 'api.config.com'));
});

it('sends correct API request', function (): void {
    Http::fake([
        'api.emailverification.com/*' => Http::response([
            'valid' => true,
            'disposable' => false,
        ], 200),
    ]);

    $service = new ExampleEmailVerificationService(
        apiKey: 'test-key',
        apiUrl: 'https://api.emailverification.com',
        timeout: 10,
    );

    $service->verify('john@example.com');

    Http::assertSent(fn ($request): bool => $request->hasHeader('X-API-Key', 'test-key')
        && $request->url() === 'https://api.emailverification.com/verify?email=john%40example.com'
        && $request->method() === 'GET');
});
