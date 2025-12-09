<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Opportunities\OpportunityMetricsService;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Example widget demonstrating service injection in Filament widgets.
 *
 * Services are injected via constructor for widget data.
 * Use singleton services with caching for performance.
 */
final class ExampleServiceWidget extends BaseWidget
{
    public function __construct(
        private readonly OpportunityMetricsService $metricsService,
    ) {
        parent::__construct();
    }

    protected function getStats(): array
    {
        $teamId = Filament::getTenant()->id;
        $metrics = $this->metricsService->getTeamMetrics($teamId);

        return [
            Stat::make(__('app.labels.total_opportunities'), $metrics['total_count'])
                ->description(__('app.labels.active_opportunities'))
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('primary'),

            Stat::make(__('app.labels.total_value'), $metrics['total_value_formatted'])
                ->description($metrics['change_percentage'] . '% ' . __('app.labels.from_last_month'))
                ->descriptionIcon($metrics['trend_icon'])
                ->color($metrics['trend_color']),

            Stat::make(__('app.labels.win_rate'), $metrics['win_rate'] . '%')
                ->description(__('app.labels.closed_won'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
