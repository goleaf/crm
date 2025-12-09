<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Widgets;

use App\Enums\CreationSource;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Task;
use App\Services\Opportunities\OpportunityMetricsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;
use Relaticle\SystemAdmin\Filament\Widgets\Concerns\HasCustomFieldQueries;

final class BusinessOverviewWidget extends BaseWidget
{
    use HasCustomFieldQueries;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $businessData = $this->getBusinessData();

        return [
            Stat::make('Pipeline Value', $this->formatCurrency($businessData['pipeline_value']))
                ->description($this->getPipelineDescription($businessData['pipeline_value']))
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success')
                ->chart($businessData['pipeline_trend'])
                ->extraAttributes([
                    'class' => 'relative overflow-hidden',
                ]),

            Stat::make('Weighted Forecast', $this->formatCurrency($businessData['weighted_pipeline']))
                ->description('Probability-weighted revenue forecast')
                ->descriptionIcon('heroicon-o-sparkles')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'relative overflow-hidden',
                ]),

            Stat::make('Active Opportunities', number_format($businessData['total_opportunities']))
                ->description($this->getGrowthDescription($businessData['opportunities_growth'], 'opportunities'))
                ->descriptionIcon($businessData['opportunities_growth'] >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($this->getGrowthColor($businessData['opportunities_growth']))
                ->extraAttributes([
                    'class' => 'relative overflow-hidden',
                ]),

            Stat::make('Avg Sales Cycle', $businessData['avg_sales_cycle'] . ' days')
                ->description($this->getSalesCycleDescription($businessData['avg_sales_cycle']))
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'relative overflow-hidden',
                ]),

            Stat::make('Task Completion', $businessData['completion_rate'] . '%')
                ->description($this->getCompletionDescription($businessData['completion_rate']))
                ->descriptionIcon($this->getCompletionIcon($businessData['completion_rate']))
                ->color($this->getCompletionColor($businessData['completion_rate']))
                ->extraAttributes([
                    'class' => 'relative overflow-hidden',
                ]),

            Stat::make('Total Companies', number_format($businessData['total_companies']))
                ->description($this->getGrowthDescription($businessData['companies_growth'], 'companies'))
                ->descriptionIcon($businessData['companies_growth'] >= 0 ? 'heroicon-o-building-office-2' : 'heroicon-o-building-office')
                ->color($this->getGrowthColor($businessData['companies_growth']))
                ->extraAttributes([
                    'class' => 'relative overflow-hidden',
                ]),
        ];
    }

    /**
     * @return array{pipeline_value: float, weighted_pipeline: float, total_opportunities: int, completion_rate: float, total_companies: int, opportunities_growth: float, companies_growth: float, pipeline_trend: array<int, float>, avg_sales_cycle: float}
     */
    private function getBusinessData(): array
    {
        $metrics = resolve(OpportunityMetricsService::class);
        $opportunities = $this->getOpportunitiesWithMetrics();

        $pipelineValue = $opportunities
            ->map(fn (Opportunity $opportunity): float => $metrics->amount($opportunity) ?? 0.0)
            ->sum();

        $weightedPipeline = $opportunities
            ->map(fn (Opportunity $opportunity): float => $metrics->weightedAmount($opportunity) ?? 0.0)
            ->sum();

        $salesCycleSamples = $opportunities
            ->map(fn (Opportunity $opportunity): ?int => $metrics->salesCycleDays($opportunity))
            ->filter(fn (?int $value): bool => $value !== null)
            ->values();

        $avgSalesCycle = $salesCycleSamples->isNotEmpty()
            ? round($salesCycleSamples->avg(), 1)
            : 0.0;

        $totalOpportunities = $opportunities->count();

        $totalTasks = Task::where('creation_source', '!=', CreationSource::SYSTEM->value)->count();
        $completedTasks = $this->countCompletedEntities('tasks', 'task', 'status');
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        $totalCompanies = Company::where('creation_source', '!=', CreationSource::SYSTEM->value)->count();

        [$opportunitiesGrowth, $companiesGrowth] = $this->calculateMonthlyGrowth();
        $pipelineTrend = $this->generatePipelineTrend($metrics);

        return [
            'pipeline_value' => $pipelineValue,
            'weighted_pipeline' => $weightedPipeline,
            'total_opportunities' => $totalOpportunities,
            'completion_rate' => $completionRate,
            'total_companies' => $totalCompanies,
            'opportunities_growth' => $opportunitiesGrowth,
            'companies_growth' => $companiesGrowth,
            'pipeline_trend' => $pipelineTrend,
            'avg_sales_cycle' => $avgSalesCycle,
        ];
    }

    /**
     * @return Collection<int, Opportunity>
     */
    private function getOpportunitiesWithMetrics(): Collection
    {
        return Opportunity::query()
            ->withCustomFieldValues()
            ->whereNull('deleted_at')
            ->where('creation_source', '!=', CreationSource::SYSTEM->value)
            ->get();
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function calculateMonthlyGrowth(): array
    {
        $opportunitiesThisMonth = Opportunity::query()
            ->monthToDate()
            ->where('creation_source', '!=', CreationSource::SYSTEM->value)
            ->count();
        $opportunitiesLastMonth = Opportunity::query()
            ->ofLastMonth(startFrom: now())
            ->where('creation_source', '!=', CreationSource::SYSTEM->value)
            ->count();

        $companiesThisMonth = Company::query()
            ->monthToDate()
            ->where('creation_source', '!=', CreationSource::SYSTEM->value)
            ->count();
        $companiesLastMonth = Company::query()
            ->ofLastMonth(startFrom: now())
            ->where('creation_source', '!=', CreationSource::SYSTEM->value)
            ->count();

        $opportunitiesGrowth = $this->calculateGrowthRate($opportunitiesThisMonth, $opportunitiesLastMonth);
        $companiesGrowth = $this->calculateGrowthRate($companiesThisMonth, $companiesLastMonth);

        return [$opportunitiesGrowth, $companiesGrowth];
    }

    private function calculateGrowthRate(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100);
    }

    /**
     * @return array<int, float>
     */
    private function generatePipelineTrend(OpportunityMetricsService $metrics): array
    {
        $baseQuery = Opportunity::query()
            ->withCustomFieldValues()
            ->whereNull('deleted_at')
            ->where('creation_source', '!=', CreationSource::SYSTEM->value);

        return collect(range(6, 0))
            ->map(function (int $daysAgo) use ($baseQuery, $metrics): float {
                $startFrom = now()->subDays($daysAgo);

                return (clone $baseQuery)
                    ->ofToday(startFrom: $startFrom)
                    ->get()
                    ->map(fn (Opportunity $opportunity): float => $metrics->amount($opportunity) ?? 0.0)
                    ->sum();
            })
            ->all();
    }

    private function formatCurrency(float $amount): string
    {
        return match (true) {
            $amount >= 1000000 => '$' . number_format($amount / 1000000, 1) . 'M',
            $amount >= 1000 => '$' . number_format($amount / 1000, 1) . 'K',
            default => '$' . number_format($amount, 0)
        };
    }

    private function getPipelineDescription(float $amount): string
    {
        return match (true) {
            $amount >= 1000000 => 'Revenue potential across all opportunities',
            $amount >= 100000 => 'Strong pipeline building momentum',
            $amount > 0 => 'Early stage opportunities in pipeline',
            default => 'No revenue opportunities tracked yet'
        };
    }

    private function getSalesCycleDescription(float $averageDays): string
    {
        return match (true) {
            $averageDays === 0.0 => 'No close dates provided yet',
            $averageDays <= 15 => 'Fast-moving deals through the funnel',
            $averageDays <= 45 => 'Healthy sales cadence',
            $averageDays <= 90 => 'Longer cycles, watch for stalls',
            default => 'Deals aging beyond target cycles',
        };
    }

    private function getGrowthDescription(float $growth, string $type): string
    {
        return match (true) {
            $growth > 50 => "Exceptional {$type} growth this month",
            $growth > 20 => "Strong {$type} growth this month",
            $growth > 0 => "Positive {$type} growth this month",
            default => "Declining {$type} this month"
        };
    }

    private function getCompletionDescription(float $rate): string
    {
        return match (true) {
            $rate >= 90 => 'Exceptional team productivity',
            $rate >= 70 => 'Strong team performance',
            $rate >= 50 => 'Average team productivity',
            $rate > 0 => 'Below average performance',
            default => 'No completed tasks tracked'
        };
    }

    private function getGrowthColor(float $growth): string
    {
        return match (true) {
            $growth > 20 => 'success',
            $growth > 0 => 'info',
            $growth === 0.0 => 'warning',
            default => 'danger'
        };
    }

    private function getCompletionColor(float $rate): string
    {
        return match (true) {
            $rate >= 80 => 'success',
            $rate >= 60 => 'info',
            $rate >= 40 => 'warning',
            default => 'danger'
        };
    }

    private function getCompletionIcon(float $rate): string
    {
        return match (true) {
            $rate >= 80 => 'heroicon-o-check-badge',
            $rate >= 60 => 'heroicon-o-check-circle',
            $rate >= 40 => 'heroicon-o-clock',
            default => 'heroicon-o-exclamation-triangle'
        };
    }
}
