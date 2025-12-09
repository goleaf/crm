<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;

final readonly class GitHubService
{
    /**
     * Get the stargazers to count for a GitHub repository
     *
     * @param string $owner        The repository owner
     * @param string $repo         The repository name
     * @param int    $cacheMinutes Minutes to cache the result (default: 15)
     */
    public function getStarsCount(?string $owner = null, ?string $repo = null, int $cacheMinutes = 15): int
    {
        $owner ??= config('laravel-crm.ui.github_owner');
        $repo ??= config('laravel-crm.ui.github_repo');

        $defaultCacheMinutes = (int) config('http-clients.services.github.cache_minutes', $cacheMinutes);
        $cacheMinutes = $cacheMinutes === 15 ? $defaultCacheMinutes : $cacheMinutes;
        $cacheMinutes = $cacheMinutes > 0 ? $cacheMinutes : 1;

        if (! $owner || ! $repo) {
            return 0;
        }

        $cacheKey = "github_stars_{$owner}_{$repo}";

        $cachedValue = Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($owner, $repo): int {
            try {
                /** @var Response $response */
                $response = Http::github()
                    ->withUrlParameters([
                        'owner' => $owner,
                        'repo' => $repo,
                    ])
                    ->get('/repos/{owner}/{repo}');

                if ($response->successful()) {
                    return (int) $response->json('stargazers_count', 0);
                }

                Log::warning('Failed to fetch GitHub stars', [
                    'status' => $response->status(),
                    'owner' => $owner,
                    'repo' => $repo,
                ]);

                return 0;
            } catch (Exception $e) {
                Log::error('Error fetching GitHub stars', [
                    'owner' => $owner,
                    'repo' => $repo,
                    'message' => $e->getMessage(),
                ]);

                return 0;
            }
        });

        return is_null($cachedValue) ? 0 : (int) $cachedValue;
    }

    /**
     * Get the formatted stargazers to count for a GitHub repository
     *
     * @param string $owner        The repository owner
     * @param string $repo         The repository name
     * @param int    $cacheMinutes Minutes to cache the result (default: 15)
     */
    public function getFormattedStarsCount(?string $owner = null, ?string $repo = null, int $cacheMinutes = 15): string
    {
        $starsCount = $this->getStarsCount($owner, $repo, $cacheMinutes);

        if ($starsCount >= 1000) {
            $abbreviated = Number::abbreviate($starsCount, 1);

            return is_string($abbreviated) ? $abbreviated : (string) $starsCount;
        }

        return (string) $starsCount;
    }
}
