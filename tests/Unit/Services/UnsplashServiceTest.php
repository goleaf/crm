<?php

declare(strict_types=1);

use App\Services\Media\UnsplashService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config([
        'unsplash.access_key' => 'test-access-key',
        'unsplash.secret_key' => 'test-secret-key',
        'unsplash.http.base_url' => 'https://api.unsplash.com',
        'unsplash.http.timeout' => 30,
        'unsplash.http.retry.times' => 3,
        'unsplash.http.retry.sleep' => 1000,
        'unsplash.cache.enabled' => true,
        'unsplash.cache.ttl' => 3600,
        'unsplash.cache.prefix' => 'unsplash',
        'unsplash.utm_source' => 'test-app',
    ]);

    $this->service = resolve(UnsplashService::class);
});

it('searches photos successfully', function (): void {
    Http::fake([
        'api.unsplash.com/search/photos*' => Http::response([
            'results' => [
                [
                    'id' => 'test-photo-1',
                    'description' => 'Beautiful landscape',
                    'urls' => ['regular' => 'https://example.com/photo.jpg'],
                ],
            ],
            'total' => 1,
            'total_pages' => 1,
        ], 200),
    ]);

    $results = $this->service->searchPhotos('nature');

    expect($results)
        ->toBeArray()
        ->toHaveKey('results')
        ->toHaveKey('total')
        ->and($results['results'])->toHaveCount(1)
        ->and($results['total'])->toBe(1);
});

it('handles search failures gracefully', function (): void {
    Http::fake([
        'api.unsplash.com/*' => Http::response([], 500),
    ]);

    $results = $this->service->searchPhotos('nature');

    expect($results)
        ->toBeArray()
        ->and($results['results'])->toBeEmpty()
        ->and($results['total'])->toBe(0);
});

it('gets random photo successfully', function (): void {
    Http::fake([
        'api.unsplash.com/photos/random*' => Http::response([
            [
                'id' => 'random-photo',
                'description' => 'Random photo',
                'urls' => ['regular' => 'https://example.com/random.jpg'],
            ],
        ], 200),
    ]);

    $photos = $this->service->randomPhoto(count: 1);

    expect($photos)
        ->toBeArray()
        ->toHaveCount(1)
        ->and($photos[0]['id'])->toBe('random-photo');
});

it('gets photo details by id', function (): void {
    Http::fake([
        'api.unsplash.com/photos/test-id' => Http::response([
            'id' => 'test-id',
            'description' => 'Test photo',
            'urls' => ['regular' => 'https://example.com/test.jpg'],
            'user' => [
                'name' => 'John Doe',
                'username' => 'johndoe',
            ],
        ], 200),
    ]);

    $photo = $this->service->getPhoto('test-id');

    expect($photo)
        ->toBeArray()
        ->toHaveKey('id')
        ->and($photo['id'])->toBe('test-id')
        ->and($photo['user']['name'])->toBe('John Doe');
});

it('returns null for non-existent photo', function (): void {
    Http::fake([
        'api.unsplash.com/photos/*' => Http::response([], 404),
    ]);

    $photo = $this->service->getPhoto('non-existent');

    expect($photo)->toBeNull();
});

it('tracks download successfully', function (): void {
    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $result = $this->service->trackDownload('https://api.unsplash.com/photos/test/download');

    expect($result)->toBeTrue();
});

it('downloads photo to storage', function (): void {
    Storage::fake('public');

    Http::fake([
        'https://images.unsplash.com/*' => Http::response('fake-image-content', 200),
    ]);

    $path = $this->service->downloadPhoto(
        url: 'https://images.unsplash.com/photo-123',
        filename: 'test-photo.jpg',
        disk: 'public',
        path: 'unsplash'
    );

    expect($path)->toBe('unsplash/test-photo.jpg');
    Storage::disk('public')->assertExists('unsplash/test-photo.jpg');
});

it('returns null when download fails', function (): void {
    Storage::fake('public');

    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $path = $this->service->downloadPhoto(
        url: 'https://images.unsplash.com/photo-123',
        filename: 'test-photo.jpg'
    );

    expect($path)->toBeNull();
});

it('caches search results', function (): void {
    Cache::flush();

    Http::fake([
        'api.unsplash.com/search/photos*' => Http::response([
            'results' => [['id' => 'cached-photo']],
            'total' => 1,
            'total_pages' => 1,
        ], 200),
    ]);

    // First call - should hit API
    $this->service->searchPhotos('nature');

    // Second call - should use cache
    $this->service->searchPhotos('nature');

    // Should only make one HTTP request
    Http::assertSentCount(1);
});

it('clears cache successfully', function (): void {
    Cache::flush();

    Http::fake([
        'api.unsplash.com/search/photos*' => Http::response([
            'results' => [],
            'total' => 0,
            'total_pages' => 0,
        ], 200),
    ]);

    // Make a request to populate cache
    $this->service->searchPhotos('nature');

    // Clear cache
    $result = $this->service->clearCache();

    expect($result)->toBeTrue();

    // Next request should hit API again
    $this->service->searchPhotos('nature');

    Http::assertSentCount(2);
});

it('searches collections successfully', function (): void {
    Http::fake([
        'api.unsplash.com/search/collections*' => Http::response([
            'results' => [
                [
                    'id' => 'collection-1',
                    'title' => 'Nature Collection',
                ],
            ],
            'total' => 1,
        ], 200),
    ]);

    $results = $this->service->searchCollections('nature');

    expect($results)
        ->toBeArray()
        ->toHaveKey('results')
        ->and($results['results'])->toHaveCount(1);
});

it('gets collection photos successfully', function (): void {
    Http::fake([
        'api.unsplash.com/collections/*/photos*' => Http::response([
            [
                'id' => 'photo-1',
                'description' => 'Photo from collection',
            ],
        ], 200),
    ]);

    $photos = $this->service->getCollectionPhotos('123456');

    expect($photos)
        ->toBeArray()
        ->toHaveCount(1)
        ->and($photos[0]['id'])->toBe('photo-1');
});

it('retries on rate limit errors', function (): void {
    Http::fake([
        'api.unsplash.com/search/photos*' => Http::sequence()
            ->push([], 429)
            ->push([
                'results' => [['id' => 'success']],
                'total' => 1,
                'total_pages' => 1,
            ], 200),
    ]);

    $results = $this->service->searchPhotos('nature');

    expect($results['results'])->toHaveCount(1);
    Http::assertSentCount(2);
});

it('includes proper authorization headers', function (): void {
    Http::fake([
        'api.unsplash.com/*' => Http::response([], 200),
    ]);

    $this->service->searchPhotos('test');

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization')
        && str_starts_with((string) $request->header('Authorization')[0], 'Client-ID'));
});

it('includes user agent with app name', function (): void {
    Http::fake([
        'api.unsplash.com/*' => Http::response([], 200),
    ]);

    $this->service->searchPhotos('test');

    Http::assertSent(fn ($request): bool => $request->hasHeader('User-Agent')
        && str_contains((string) $request->header('User-Agent')[0], (string) config('app.name')));
});
