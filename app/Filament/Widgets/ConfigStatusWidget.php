<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Config\ConfigCheckerService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class ConfigStatusWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $service = resolve(ConfigCheckerService::class);
        $result = $service->getCachedCheck();
        $isHealthy = $result['status'] === 'healthy';
        $issuesCount = count($result['issues'] ?? []);

        return [
            Stat::make('Config Status', $isHealthy ? 'Healthy' : 'Issues Found')
                ->description($isHealthy ? 'All config keys defined' : "{$issuesCount} missing keys detected")
                ->descriptionIcon($isHealthy ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($isHealthy ? 'success' : 'danger'),
        ];
    }
}
