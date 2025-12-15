<?php

declare(strict_types=1);

namespace App\Services\ShareLink;

use Grazulex\ShareLink\Models\ShareLink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ShareLink Service
 *
 * Provides centralized access to shareable link generation and management.
 * All methods return cached results where appropriate.
 */
final readonly class ShareLinkService
{
    public function __construct(
        private int $defaultCacheTtl = 3600,
    ) {}

    /**
     * Create a shareable link for any model
     */
    public function createLink(
        Model $model,
        ?Carbon $expiresAt = null,
        ?int $maxClicks = null,
        ?string $password = null,
        array $metadata = [],
    ): ShareLink {
        $link = ShareLink::createForResource($model);

        if ($expiresAt instanceof \Illuminate\Support\Carbon) {
            $link->expires_at = $expiresAt;
        }

        if ($maxClicks) {
            $link->max_clicks = $maxClicks;
        }

        if ($password) {
            $link->password = bcrypt($password);
        }

        if ($metadata !== []) {
            $link->metadata = $metadata;
        }

        // Track creator if user tracking is enabled
        if (config('sharelink.user_tracking.enabled') && auth()->check()) {
            $link->created_by = auth()->id();
        }

        $link->save();

        Log::info('ShareLink created', [
            'link_id' => $link->id,
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'expires_at' => $expiresAt?->toDateTimeString(),
            'max_clicks' => $maxClicks,
            'has_password' => ! is_null($password),
        ]);

        return $link;
    }

    /**
     * Create a temporary link (expires in X hours)
     */
    public function createTemporaryLink(
        Model $model,
        int $hours = 24,
        ?int $maxClicks = null,
        array $metadata = [],
    ): ShareLink {
        return $this->createLink(
            model: $model,
            expiresAt: now()->addHours($hours),
            maxClicks: $maxClicks,
            metadata: $metadata,
        );
    }

    /**
     * Create a one-time link (burn after reading)
     */
    public function createOneTimeLink(
        Model $model,
        ?Carbon $expiresAt = null,
        array $metadata = [],
    ): ShareLink {
        $metadata['burn_after_reading'] = true;

        return $this->createLink(
            model: $model,
            expiresAt: $expiresAt ?? now()->addDays(7),
            maxClicks: 1,
            metadata: $metadata,
        );
    }

    /**
     * Create a password-protected link
     */
    public function createProtectedLink(
        Model $model,
        string $password,
        ?Carbon $expiresAt = null,
        ?int $maxClicks = null,
        array $metadata = [],
    ): ShareLink {
        return $this->createLink(
            model: $model,
            expiresAt: $expiresAt,
            maxClicks: $maxClicks,
            password: $password,
            metadata: $metadata,
        );
    }

    /**
     * Get all active links for a model
     */
    public function getActiveLinksForModel(Model $model): \Illuminate\Support\Collection
    {
        $cacheKey = "sharelinks.model.{$model->getMorphClass()}.{$model->getKey()}";

        return Cache::remember($cacheKey, $this->defaultCacheTtl, fn () => ShareLink::query()
            ->whereJsonContains('resource->type', $model->getMorphClass())
            ->whereJsonContains('resource->id', (string) $model->getKey())
            ->whereNull('revoked_at')
            ->where(function (\Illuminate\Contracts\Database\Query\Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })->latest()
            ->get());
    }

    /**
     * Get link statistics
     */
    public function getLinkStats(ShareLink $link): array
    {
        return [
            'total_clicks' => $link->click_count,
            'remaining_clicks' => $link->max_clicks ? max(0, $link->max_clicks - $link->click_count) : null,
            'is_expired' => $link->expires_at && $link->expires_at->isPast(),
            'is_revoked' => ! is_null($link->revoked_at),
            'is_active' => $this->isLinkActive($link),
            'first_accessed' => $link->first_access_at?->toDateTimeString(),
            'last_accessed' => $link->last_access_at?->toDateTimeString(),
            'days_until_expiry' => $link->expires_at ? now()->diffInDays($link->expires_at, false) : null,
        ];
    }

    /**
     * Check if a link is still active
     */
    public function isLinkActive(ShareLink $link): bool
    {
        if ($link->revoked_at) {
            return false;
        }

        if ($link->expires_at && $link->expires_at->isPast()) {
            return false;
        }

        return ! ($link->max_clicks && $link->click_count >= $link->max_clicks);
    }

    /**
     * Revoke a link
     */
    public function revokeLink(ShareLink $link): bool
    {
        $link->revoked_at = now();
        $result = $link->save();

        if ($result) {
            $this->clearCache($link);

            Log::info('ShareLink revoked', [
                'link_id' => $link->id,
                'revoked_by' => auth()->id(),
            ]);
        }

        return $result;
    }

    /**
     * Extend link expiration
     */
    public function extendLink(ShareLink $link, Carbon $newExpiresAt): bool
    {
        $oldExpiry = $link->expires_at;
        $link->expires_at = $newExpiresAt;
        $result = $link->save();

        if ($result) {
            $this->clearCache($link);

            Log::info('ShareLink extended', [
                'link_id' => $link->id,
                'old_expiry' => $oldExpiry?->toDateTimeString(),
                'new_expiry' => $newExpiresAt->toDateTimeString(),
                'extended_by' => auth()->id(),
            ]);
        }

        return $result;
    }

    /**
     * Get team-scoped links (if applicable)
     */
    public function getTeamLinks(int $teamId): \Illuminate\Support\Collection
    {
        $cacheKey = "sharelinks.team.{$teamId}";

        return Cache::remember($cacheKey, $this->defaultCacheTtl, fn () => ShareLink::query()
            ->whereJsonContains('metadata->team_id', $teamId)->latest()
            ->get());
    }

    /**
     * Get user's created links
     */
    public function getUserLinks(int $userId): \Illuminate\Support\Collection
    {
        if (! config('sharelink.user_tracking.enabled')) {
            return collect();
        }

        $cacheKey = "sharelinks.user.{$userId}";

        return Cache::remember($cacheKey, $this->defaultCacheTtl, fn () => ShareLink::query()
            ->where('created_by', $userId)->latest()
            ->get());
    }

    /**
     * Clear cache for a specific link
     */
    public function clearCache(?ShareLink $link = null): void
    {
        if ($link instanceof \Grazulex\ShareLink\Models\ShareLink) {
            $resource = $link->resource;
            if (isset($resource['type'], $resource['id'])) {
                Cache::forget("sharelinks.model.{$resource['type']}.{$resource['id']}");
            }

            if ($link->created_by) {
                Cache::forget("sharelinks.user.{$link->created_by}");
            }

            if (isset($link->metadata['team_id'])) {
                Cache::forget("sharelinks.team.{$link->metadata['team_id']}");
            }
        }

        // Clear global stats cache
        Cache::forget('sharelinks.stats');
    }

    /**
     * Get global statistics
     */
    public function getGlobalStats(): array
    {
        return Cache::remember('sharelinks.stats', $this->defaultCacheTtl, function (): array {
            $total = ShareLink::count();
            $active = ShareLink::whereNull('revoked_at')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->count();

            $expired = ShareLink::whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->whereNull('revoked_at')
                ->count();

            $revoked = ShareLink::whereNotNull('revoked_at')->count();

            $totalClicks = ShareLink::sum('click_count');

            return [
                'total_links' => $total,
                'active_links' => $active,
                'expired_links' => $expired,
                'revoked_links' => $revoked,
                'total_clicks' => $totalClicks,
                'average_clicks' => $total > 0 ? round($totalClicks / $total, 2) : 0,
            ];
        });
    }
}
