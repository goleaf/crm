<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\CrmStatsOverview;
use App\Filament\Widgets\PipelinePerformanceChart;
use App\Filament\Widgets\QuickActions;
use App\Filament\Widgets\RecentActivity;
use Filament\Pages\Dashboard as BaseDashboard;

final class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -3;

    /**
     * @return array<class-string<\Filament\Widgets\Widget>>
     */
    public function getWidgets(): array
    {
        return [
            CrmStatsOverview::class,
            PipelinePerformanceChart::class,
            QuickActions::class,
            RecentActivity::class,
        ];
    }

    public function getColumns(): int
    {
        return 2;
    }
}
