<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Dgtlss\Warden\Services\WardenService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class SecurityStatusWidget extends BaseWidget
{
    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        try {
            $warden = resolve(WardenService::class);
            $result = $warden->getLastAuditResult();

            if (! $result) {
                return [
                    Stat::make(__('app.labels.security_status'), __('app.labels.no_audit_data'))
                        ->description(__('app.messages.run_security_audit'))
                        ->descriptionIcon('heroicon-o-shield-exclamation')
                        ->color('warning'),
                ];
            }

            return [
                Stat::make(
                    __('app.labels.vulnerabilities'),
                    $result->getVulnerabilityCount(),
                )
                    ->description(__('app.labels.security_issues_found'))
                    ->descriptionIcon($result->hasVulnerabilities() ? 'heroicon-o-shield-exclamation' : 'heroicon-o-shield-check')
                    ->color($result->hasVulnerabilities() ? 'danger' : 'success')
                    ->chart($this->getVulnerabilityTrend()),

                Stat::make(
                    __('app.labels.packages_audited'),
                    $result->getPackagesAudited(),
                )
                    ->description(__('app.labels.dependencies_checked'))
                    ->descriptionIcon('heroicon-o-cube')
                    ->color('info'),

                Stat::make(
                    __('app.labels.last_audit'),
                    $result->getAuditTimestamp()?->diffForHumans() ?? __('app.labels.never'),
                )
                    ->description(__('app.labels.last_security_check'))
                    ->descriptionIcon('heroicon-o-clock')
                    ->color('gray'),
            ];
        } catch (\Exception $e) {
            return [
                Stat::make(__('app.labels.security_status'), __('app.labels.error'))
                    ->description($e->getMessage())
                    ->descriptionIcon('heroicon-o-exclamation-triangle')
                    ->color('danger'),
            ];
        }
    }

    private function getVulnerabilityTrend(): array
    {
        // Return empty array for now - can be enhanced with historical data
        return [];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view_security_audit') ?? false;
    }
}
