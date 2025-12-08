<?php

declare(strict_types=1);

namespace App\Filament\Widgets\System;

use App\Services\Testing\CodeCoverageService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CodeCoverageWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 10;

    public function __construct(
        private readonly CodeCoverageService $coverageService
    ) {
        parent::__construct();
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view_code_coverage') ?? false;
    }

    protected function getStats(): array
    {
        $stats = $this->coverageService->getCoverageStats();
        $trend = $this->coverageService->getCoverageTrend();

        $trendIcon = match ($trend) {
            'up' => 'heroicon-o-arrow-trending-up',
            'down' => 'heroicon-o-arrow-trending-down',
            default => 'heroicon-o-minus',
        };

        $trendColor = match ($trend) {
            'up' => 'success',
            'down' => 'danger',
            default => 'gray',
        };

        $overallColor = match (true) {
            $stats['overall'] >= 80 => 'success',
            $stats['overall'] >= 60 => 'warning',
            default => 'danger',
        };

        return [
            Stat::make(__('app.labels.overall_coverage'), number_format($stats['overall'], 1).'%')
                ->description($stats['covered_statements'].' / '.$stats['total_statements'].' '.__('app.labels.lines'))
                ->descriptionIcon($trendIcon)
                ->color($overallColor)
                ->chart($this->getCoverageChart())
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make(__('app.labels.method_coverage'), number_format($stats['methods'], 1).'%')
                ->description($stats['covered_methods'].' / '.$stats['total_methods'].' '.__('app.labels.methods'))
                ->descriptionIcon('heroicon-o-code-bracket')
                ->color($stats['methods'] >= 80 ? 'success' : 'warning'),

            Stat::make(__('app.labels.class_coverage'), number_format($stats['classes'], 1).'%')
                ->description($stats['covered_classes'].' / '.$stats['total_classes'].' '.__('app.labels.classes'))
                ->descriptionIcon('heroicon-o-cube')
                ->color($stats['classes'] >= 80 ? 'success' : 'warning'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_coverage')
                ->label(__('app.actions.run_coverage'))
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action(function () {
                    $result = $this->coverageService->runCoverage();

                    if ($result['success']) {
                        Notification::make()
                            ->title(__('app.notifications.coverage_generated'))
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('app.notifications.coverage_failed'))
                            ->body($result['error'])
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading(__('app.modals.run_coverage'))
                ->modalDescription(__('app.modals.run_coverage_description'))
                ->modalSubmitActionLabel(__('app.actions.run')),

            Action::make('view_report')
                ->label(__('app.actions.view_report'))
                ->icon('heroicon-o-document-text')
                ->url(fn () => route('filament.app.pages.system.code-coverage'))
                ->color('gray'),

            Action::make('refresh')
                ->label(__('app.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->coverageService->clearCache();

                    Notification::make()
                        ->title(__('app.notifications.cache_cleared'))
                        ->success()
                        ->send();
                })
                ->color('gray'),
        ];
    }

    private function getCoverageChart(): array
    {
        $history = $this->coverageService->getCoverageHistory(7);

        return $history->pluck('coverage')->toArray();
    }
}
