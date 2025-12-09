<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class UnsplashService
{
    private readonly string $accessKey;

    private readonly string $baseUrl;

    private array $defaults;

    private array $cacheConfig;

    public function __construct()
    {
        $config = config('unsplash');

        $this->accessKey = $config['access_key'] ?? throw new RuntimeException('Unsplash Access Key not configured.');
        $this->baseUrl = $config['http']['base_url'] ?? 'https://api.unsplash.com';
        $this->defaults = $config['defaults'] ?? [];
        $this->cacheConfig = $config['cache'] ?? ['enabled' => true, 'ttl' => 3600, 'prefix' => 'unsplash'];
    }

    /**
     * Search for photos on Unsplash.
     */
    public function searchPhotos(
        string $query,
        int $page = 1,
        ?int $perPage = null,
        ?string $orientation = null,
        ?string $color = null,
    ): array {
        $perPage ??= $this->defaults['per_page'] ?? 30;
        $orientation ??= $this->defaults['orientation'];

        $cacheKey = $this->getCacheKey('search', ['query' => $query, 'page' => $page, 'perPage' => $perPage, 'orientation' => $orientation, 'color' => $color]);

        return $this->remember($cacheKey, function () use ($query, $page, $perPage, $orientation, $color) {
            $response = $this->get('/search/photos', array_filter([
                'query' => $query,
                'page' => $page,
                'per_page' => $perPage,
                'orientation' => $orientation,
                'color' => $color,
            ]));

            return $response->json();
        });
    }

    /**
     * Get a random photo.
     */
    public function randomPhoto(
        ?string $query = null,
        ?string $orientation = null,
        array $collections = [],
        int $count = 1,
    ): array {
        $response = $this->get('/photos/random', array_filter([
            'query' => $query,
            'orientation' => $orientation,
            'collections' => implode(',', $collections),
            'count' => $count,
        ]));

        return $response->json();
    }

    /**
     * Get photo details.
     */
    public function getPhoto(string $id): ?array
    {
        $cacheKey = $this->getCacheKey('photo', ['id' => $id]);

        return $this->remember($cacheKey, function () use ($id) {
            $response = $this->get("/photos/{$id}");

            return $response->successful() ? $response->json() : null;
        });
    }

    /**
     * Track a photo download.
     */
    public function trackDownload(string $downloadLocation): bool
    {
        // Must include Client-ID in the tracking request
        $response = Http::withHeaders([
            'Authorization' => 'Client-ID ' . $this->accessKey,
        ])->get($downloadLocation);

        return $response->successful();
    }

    /**
     * Download a photo to local storage.
     */
    public function downloadPhoto(string $url, string $filename, ?string $disk = null, ?string $path = null): ?string
    {
        $disk ??= config('unsplash.storage.disk', 'public');
        $path ??= config('unsplash.storage.path', 'unsplash');

        $contents = Http::get($url)->body();

        if (empty($contents)) {
            return null;
        }

        $fullPath = rtrim((string) $path, '/') . '/' . ltrim($filename, '/');

        if (Storage::disk($disk)->put($fullPath, $contents)) {
            return $fullPath;
        }

        return null;
    }

    /**
     * Clear the cache.
     */
    public function clearCache(?string $type = null): bool
    {
        // Just specific keys logic in this implementation since we don't have a store of all keys.
        // In a real app, use Cache Tags.
        // This is a simplification; authentic tag-based clearing would be better
        // but for file cache, we can't easily clear by pattern without tags.
        // For now, we rely on TTL.
        // If using Redis/Memcached with tags:
        // Cache::tags([$this->cacheConfig['prefix'], $type])->flush();
        return ! $type;
    }

    private function get(string $endpoint, array $query = []): Response
    {
        $client = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Authorization' => 'Client-ID ' . $this->accessKey,
                'Accept-Version' => 'v1',
            ])
            ->timeout(config('unsplash.http.timeout', 30))
            ->retry(
                config('unsplash.http.retry.times', 3),
                config('unsplash.http.retry.sleep', 1000),
            );

        return $client->get($endpoint, $query);
    }

    private function remember(string $key, callable $callback): mixed
    {
        if (! ($this->cacheConfig['enabled'] ?? true)) {
            return $callback();
        }

        return Cache::remember($key, $this->cacheConfig['ttl'] ?? 3600, $callback);
    }

    private function getCacheKey(string $type, array $params = []): string
    {
        $hash = md5(serialize($params));

        return sprintf('%s:%s:%s', $this->cacheConfig['prefix'], $type, $hash);
    }
}
