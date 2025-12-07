<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\PeopleRepositoryInterface;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\People;
use App\Services\Opportunities\OpportunityMetricsService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final readonly class CustomerProfileService
{
    public function __construct(
        private OpportunityMetricsService $opportunityMetrics,
        private PeopleRepositoryInterface $peopleRepository,
        private CompanyRepositoryInterface $companyRepository
    ) {}

    /**
     * Build a unified view of the customer's profile, metrics, and history.
     *
     * @return array{
     *     entity: Company|People|null,
     *     segment: list<string>,
     *     lifecycle: string,
     *     metrics: array<string, float|int>,
     *     timeline: Collection<int, array<string, mixed>>
     * }
     */
    public function build(Customer $customer): array
    {
        $entity = $this->resolveEntity($customer);

        if (! $entity instanceof \App\Models\Company && ! $entity instanceof \App\Models\People) {
            return [
                'entity' => null,
                'segment' => [],
                'lifecycle' => 'Unknown',
                'metrics' => [
                    'lifetime_value' => 0.0,
                    'open_pipeline' => 0.0,
                    'active_opportunities' => 0,
                ],
                'timeline' => collect(),
            ];
        }

        $opportunities = $this->opportunitiesFor($entity);
        $metrics = $this->calculateMetrics($opportunities);

        return [
            'entity' => $entity,
            'segment' => $this->segmentFor($entity),
            'lifecycle' => $this->lifecycleFor($metrics),
            'metrics' => $metrics,
            'timeline' => $this->timelineFor($entity),
        ];
    }

    private function resolveEntity(Customer $customer): Company|People|null
    {
        return match ($customer->type) {
            'company' => $this->companyRepository->find($customer->entity_id),
            'person' => $this->peopleRepository->find($customer->entity_id),
            default => null,
        };
    }

    /**
     * @param  Company|People  $entity
     * @return EloquentCollection<int, Opportunity>
     */
    private function opportunitiesFor(Model $entity): EloquentCollection
    {
        $opportunities = collect();

        if ($entity instanceof Company) {
            $opportunities = $opportunities->merge(
                $entity->opportunities()->with('customFieldValues.customField')->get()
            );
        }

        if ($entity instanceof People) {
            $opportunities = $opportunities->merge(
                $entity->opportunities()->with('customFieldValues.customField')->get()
            );

            if ($entity->company instanceof Company) {
                $opportunities = $opportunities->merge(
                    $entity->company->opportunities()->with('customFieldValues.customField')->get()
                );
            }
        }

        /** @var EloquentCollection<int, Opportunity> $unique */
        $unique = $opportunities->unique(fn (Opportunity $opportunity): int => (int) $opportunity->getKey());

        return $unique->values();
    }

    /**
     * @param  EloquentCollection<int, Opportunity>  $opportunities
     * @return array<string, float|int>
     */
    private function calculateMetrics(EloquentCollection $opportunities): array
    {
        $lifetimeValue = 0.0;
        $openPipeline = 0.0;
        $activeCount = 0;

        foreach ($opportunities as $opportunity) {
            $stage = $this->opportunityMetrics->stageLabel($opportunity);
            $amount = $this->opportunityMetrics->amount($opportunity) ?? 0.0;

            if ($stage === 'Closed Won') {
                $lifetimeValue += $amount;

                continue;
            }

            if ($stage === 'Closed Lost') {
                continue;
            }

            $activeCount++;
            $weighted = $this->opportunityMetrics->weightedAmount($opportunity) ?? $amount;
            $openPipeline += $weighted;
        }

        return [
            'lifetime_value' => round($lifetimeValue, 2),
            'open_pipeline' => round($openPipeline, 2),
            'active_opportunities' => $activeCount,
        ];
    }

    /**
     * @param  Company|People  $entity
     * @return Collection<int, array<string, mixed>>
     */
    private function timelineFor(Model $entity): Collection
    {
        if (method_exists($entity, 'getActivityTimeline')) {
            /** @var Collection<int, array<string, mixed>> $timeline */
            $timeline = $entity->getActivityTimeline(limit: 50);

            return $timeline;
        }

        return collect();
    }

    /**
     * @param  Company|People  $entity
     * @return list<string>
     */
    private function segmentFor(Model $entity): array
    {
        if ($entity instanceof People) {
            return is_array($entity->segments) ? array_values($entity->segments) : [];
        }

        if ($entity instanceof Company) {
            $entity->loadMissing('tags');

            /** @var list<string> $segments */
            $segments = $entity->tags->pluck('name')->values()->all();

            return $segments;
        }

        return [];
    }

    /**
     * @param  array<string, float|int>  $metrics
     */
    private function lifecycleFor(array $metrics): string
    {
        $lifetime = (float) ($metrics['lifetime_value'] ?? 0.0);
        $openPipeline = (float) ($metrics['open_pipeline'] ?? 0.0);

        if ($lifetime > 0.0 && $openPipeline > 0.0) {
            return 'Expansion';
        }

        if ($lifetime > 0.0) {
            return 'Active';
        }

        return 'Prospect';
    }
}
