<?php

declare(strict_types=1);

namespace App\Services\Example;

use App\Models\People;
use Illuminate\Support\Facades\Cache;

/**
 * Example query service demonstrating caching patterns.
 *
 * Query services fetch and aggregate data, often with caching.
 * Register as singleton when caching is involved.
 */
final readonly class ExampleQueryService
{
    public function __construct(
        private int $cacheTtl = 3600,
    ) {}

    /**
     * Get contact metrics with caching.
     */
    public function getContactMetrics(People $contact): array
    {
        return Cache::remember(
            "contact.metrics.{$contact->id}",
            $this->cacheTtl,
            fn (): array => $this->calculateMetrics($contact),
        );
    }

    /**
     * Clear cached metrics for a contact.
     */
    public function clearCache(People $contact): void
    {
        Cache::forget("contact.metrics.{$contact->id}");
    }

    /**
     * Calculate metrics (expensive operation).
     */
    private function calculateMetrics(People $contact): array
    {
        return [
            'total_tasks' => $contact->tasks()->count(),
            'completed_tasks' => $contact->tasks()->where('completed', true)->count(),
            'total_notes' => $contact->notes()->count(),
            'total_opportunities' => $contact->opportunities()->count(),
            'total_value' => $contact->opportunities()->sum('value'),
        ];
    }
}
