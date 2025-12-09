<?php

declare(strict_types=1);

namespace App\Services\GitHub;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class GitHubIssuesService
{
    public function __construct(
        private string $repo,
        private string $token,
        private int $cacheTtl = 300,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            config('logging.channels.github.repo', env('GITHUB_REPO', '')),
            config('logging.channels.github.token', env('GITHUB_TOKEN', '')),
        );
    }

    /**
     * Get open issues from the repository.
     */
    public function getOpenIssues(int $limit = 5): Collection
    {
        if ($this->repo === '' || $this->repo === '0' || ($this->token === '' || $this->token === '0')) {
            return collect();
        }

        return Cache::remember('github.issues.open', $this->cacheTtl, function () use ($limit) {
            try {
                $response = Http::withToken($this->token)
                    ->accept('application/vnd.github.v3+json')
                    ->get("https://api.github.com/repos/{$this->repo}/issues", [
                        'state' => 'open',
                        'sort' => 'created',
                        'direction' => 'desc',
                        'per_page' => $limit,
                    ]);

                if ($response->failed()) {
                    Log::warning('Failed to fetch GitHub issues', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return collect();
                }

                return collect($response->json())
                    ->map(fn (array $issue): array => [
                        'id' => $issue['id'],
                        'number' => $issue['number'],
                        'title' => $issue['title'],
                        'html_url' => $issue['html_url'],
                        'user' => [
                            'login' => $issue['user']['login'] ?? 'unknown',
                            'avatar_url' => $issue['user']['avatar_url'] ?? null,
                        ],
                        'labels' => collect($issue['labels'] ?? [])->map(fn ($l): array => [
                            'name' => $l['name'],
                            'color' => $l['color'],
                        ])->all(),
                        'created_at' => $issue['created_at'],
                    ]);

            } catch (\Exception $e) {
                Log::error('Exception fetching GitHub issues', ['error' => $e->getMessage()]);

                return collect();
            }
        });
    }
}
