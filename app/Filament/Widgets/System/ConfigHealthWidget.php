<?php

declare(strict_types=1);

namespace App\Filament\Widgets\System;

use App\Services\Config\ConfigCheckerService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class ConfigHealthWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        /** @var ConfigCheckerService $checker */
        $checker = resolve(ConfigCheckerService::class);
        $results = $checker->getCachedCheck();

        $status = $results['status'] ?? 'unknown';
        $issuesCount = count($results['issues'] ?? []);

        if ($status === 'healthy') {
            return [
                Stat::make('Configuration Health', 'Healthy')
                    ->description('All referenced keys exist')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success')
                    ->url(route('filament.admin.pages.system.config-checker')),
            ];
        }

        if ($status === 'issues_found') {
            return [
                Stat::make('Configuration Health', 'Issues Found')
                    ->description("$issuesCount potentially missing keys")
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger')
                    ->url(route('filament.admin.pages.system.config-checker')),
            ];
        }

        return [
            Stat::make('Configuration Health', 'Unknown')
                ->description('Run check to verify')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('gray')
                ->url(route('filament.admin.pages.system.config-checker')),
        ];
    }
}
