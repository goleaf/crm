<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Widgets;

use App\Enums\CreationSource;
use App\Models\Opportunity;
use App\Services\Opportunities\OpportunityMetricsService;
use Filament\Widgets\ChartWidget;

final class SalesAnalyticsChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    /**
     * @return array<string, mixed>
     */
    public function getColumnSpan(): array
    {
        return [
            'default' => 'full',
            'md' => 2,
            'lg' => 3,
            'xl' => 3,
            '2xl' => 3,
        ];
    }

    public function getHeading(): string
    {
        return 'Sales Pipeline Trends';
    }

    public function getDescription(): string
    {
        return 'Track your sales pipeline value, weighted forecast, and opportunities count over the last 6 months.';
    }

    public function getMaxHeight(): string
    {
        return '300px';
    }

    protected function getData(): array
    {
        $salesData = $this->getSalesData();

        return [
            'datasets' => [
                [
                    'label' => 'Pipeline Value ($)',
                    'data' => $salesData['monthly_values'],
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => '#10B981',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#10B981',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8,
                ],
                [
                    'label' => 'Weighted Forecast ($)',
                    'data' => $salesData['monthly_weighted_values'],
                    'backgroundColor' => 'rgba(234, 179, 8, 0.12)',
                    'borderColor' => '#eab308',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#eab308',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8,
                ],
                [
                    'label' => 'Opportunities Count',
                    'data' => $salesData['monthly_counts'],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => '#3B82F6',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#3B82F6',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $salesData['months'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array{months: array<int, string>, monthly_values: array<int, float>, monthly_weighted_values: array<int, float>, monthly_counts: array<int, int>}
     */
    private function getSalesData(): array
    {
        $metrics = app(OpportunityMetricsService::class);

        $opportunities = Opportunity::query()
            ->withCustomFieldValues()
            ->whereNull('deleted_at')
            ->where('creation_source', '!=', CreationSource::SYSTEM->value)
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get();

        $monthlyData = collect(range(5, 0))
            ->map(fn (int $monthsAgo): array => $this->getMonthData($monthsAgo, $opportunities, $metrics))
            ->values();

        return [
            'months' => $monthlyData->pluck('month')->toArray(),
            'monthly_values' => $monthlyData->pluck('value')->toArray(),
            'monthly_weighted_values' => $monthlyData->pluck('weighted_value')->toArray(),
            'monthly_counts' => $monthlyData->pluck('count')->toArray(),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Opportunity>  $opportunities
     * @return array{month: string, value: float, weighted_value: float, count: int}
     */
    private function getMonthData(int $monthsAgo, \Illuminate\Support\Collection $opportunities, OpportunityMetricsService $metrics): array
    {
        $month = now()->subMonths($monthsAgo);
        $monthStart = $month->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $monthlyOpportunities = $opportunities
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        return [
            'month' => $month->format('M Y'),
            'value' => $monthlyOpportunities
                ->map(fn (Opportunity $opportunity): float => $metrics->amount($opportunity) ?? 0.0)
                ->sum(),
            'weighted_value' => $monthlyOpportunities
                ->map(fn (Opportunity $opportunity): float => $metrics->weightedAmount($opportunity) ?? 0.0)
                ->sum(),
            'count' => $monthlyOpportunities->count(),
        ];
    }
}
