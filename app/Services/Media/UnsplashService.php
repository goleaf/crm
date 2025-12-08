<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final readonly class UnsplashService
{
    public function __construct(
        private string $accessKey,
        private string $baseUrl,
        private int $timeout,
        private int $retryTimes,
        private int $retrySleep,
        private bool $cacheEnabled,
        private int $cacheTtl,
        private string $cachePrefix,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            accessKey: config('unsplash.access_key'),
            baseUrl: config('unsplash.http.base_url'),
            timeout: config('unsplash.http.timeout'),
            retryTimes: config('unsplash.http.retry.times'),
            retrySleep: config('unsplash.http.retry.sleep'),
            cacheEnabled: config('unsplash.cache.enabled'),
            cacheTtl: config('unsplash.cache.ttl'),
            cachePrefix: config('unsplash.cache.prefix'),
        );
    }

    /**
     * Search photos on Unsplash
     */
    public function searchPhotos(
        string $query,
        int $page = 1,
        int $perPage = 30,
        ?string $orientation = null,
        ?string $color = null,
    ): array {
        $cacheKey = $this->getCacheKey('search', ['query' => $query, 'page' => $page, 'perPage' => $perPage, 'orientation' => $orientation, 'color' => $color]);

        return $this->remember($cacheKey, function () use ($query, $page, $perPage, $orientation, $color) {
            $response = $this->client()
                ->get('/search/photos', array_filter([
                    'query' => $query,
                    'page' => $page,
                    'per_page' => $perPage,
                    'orientation' => $orientation,
                    'color' => $color,
                ]));

            if ($response->failed()) {
                Log::warning('Unsplash search failed', [
                    'query' => $query,
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);

                return ['results' => [], 'total' => 0, 'total_pages' => 0];
            }

            return $response->json();
        });
    }

    /**
     * Get a random photo
     */
    public function randomPhoto(
        ?string $query = null,
        ?string $orientation = null,
        ?array $collections = null,
        int $count = 1,
    ): array {
        $cacheKey = $this->getCacheKey('random', ['query' => $query, 'orientation' => $orientation, 'collections' => $collections, 'count' => $count]);

        return $this->remember($cacheKey, function () use ($query, $orientation, $collections, $count) {
            $response = $this->client()
                ->get('/photos/random', array_filter([
                    'query' => $query,
                    'orientation' => $orientation,
                    'collections' => $collections ? implode(',', $collections) : null,
                    'count' => $count,
                ]));

            if ($response->failed()) {
                Log::warning('Unsplash random photo failed', [
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);

                return [];
            }

            return $response->json();
        });
    }

    /**
     * Get photo details by ID
     */
    public function getPhoto(string $id): ?array
    {
        $cacheKey = $this->getCacheKey('photo', ['id' => $id]);

        return $this->remember($cacheKey, function () use ($id) {
            $response = $this->client()->get("/photos/{$id}");

            if ($response->failed()) {
                Log::warning('Unsplash get photo failed', [
                    'id' => $id,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $response->json();
        });
    }

    /**
     * Track photo download (required by Unsplash API guidelines)
     */
    public function trackDownload(string $downloadLocation): bool
    {
        try {
            $response = $this->client()->get($downloadLocation);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to track Unsplash download', [
                'location' => $downloadLocation,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Download photo to local storage
     */
    public function downloadPhoto(
        string $url,
        string $filename,
        ?string $disk = null,
        ?string $path = null,
    ): ?string {
        $disk ??= config('unsplash.storage.disk');
        $path ??= config('unsplash.storage.path');

        try {
            $response = Http::timeout($this->timeout)->get($url);

            if ($response->failed()) {
                Log::warning('Failed to download Unsplash photo', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $fullPath = "{$path}/{$filename}";
            Storage::disk($disk)->put($fullPath, $response->body());

            return $fullPath;
        } catch (\Exception $e) {
            Log::error('Exception downloading Unsplash photo', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Search collections
     */
    public function searchCollections(string $query, int $page = 1, int $perPage = 30): array
    {
        $cacheKey = $this->getCacheKey('collections', ['query' => $query, 'page' => $page, 'perPage' => $perPage]);

        return $this->remember($cacheKey, function () use ($query, $page, $perPage) {
            $response = $this->client()
                ->get('/search/collections', [
                    'query' => $query,
                    'page' => $page,
                    'per_page' => $perPage,
                ]);

            if ($response->failed()) {
                return ['results' => [], 'total' => 0];
            }

            return $response->json();
        });
    }

    /**
     * Get collection photos
     */
    public function getCollectionPhotos(string $id, int $page = 1, int $perPage = 30): array
    {
        $cacheKey = $this->getCacheKey('collection_photos', ['id' => $id, 'page' => $page, 'perPage' => $perPage]);

        return $this->remember($cacheKey, function () use ($id, $page, $perPage) {
            $response = $this->client()
                ->get("/collections/{$id}/photos", [
                    'page' => $page,
                    'per_page' => $perPage,
                ]);

            if ($response->failed()) {
                return [];
            }

            return $response->json();
        });
    }

    /**
     * Clear cache for specific key or all Unsplash cache
     */
    public function clearCache(?string $key = null): bool
    {
        if ($key) {
            return Cache::forget($this->getCacheKey($key));
        }

        return Cache::flush();
    }

    /**
     * Get configured HTTP client
     */
    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retrySleep, fn ($exception, $request): bool =>
                // Retry on 429 (rate limit) and 5xx errors
                $exception instanceof \Illuminate\Http\Client\RequestException
                && in_array($exception->response?->status(), [429, 500, 502, 503, 504], true))
            ->withHeaders([
                'Authorization' => "Client-ID {$this->accessKey}",
                'Accept-Version' => 'v1',
            ])
            ->withUserAgent($this->getUserAgent());
    }

    /**
     * Get brand-aware user agent
     */
    private function getUserAgent(): string
    {
        $appName = config('unsplash.utm_source', config('app.name'));
        $appUrl = config('app.url');

        return "{$appName} ({$appUrl})";
    }

    /**
     * Cache helper with TTL
     */
    private function remember(string $key, callable $callback): mixed
    {
        if (! $this->cacheEnabled) {
            return $callback();
        }

        return Cache::remember($key, $this->cacheTtl, $callback);
    }

    /**
     * Generate cache key
     */
    private function getCacheKey(string $type, array $params = []): string
    {
        $hash = md5(serialize($params));

        return "{$this->cachePrefix}:{$type}:{$hash}";
    }
}
