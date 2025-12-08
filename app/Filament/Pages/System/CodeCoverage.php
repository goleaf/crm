<?php

declare(strict_types=1);

namespace App\Filament\Pages\System;

use App\Services\Testing\CodeCoverageService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\File;

final class CodeCoverage extends Page
{
    protected string $view = 'filament.pages.system.code-coverage';

    protected static ?int $navigationSort = 50;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chart-bar';
    }

    public array $stats = [];

    public array $categoryStats = [];

    public array $pcovConfig = [];

    public bool $pcovEnabled = false;

    public function __construct(
        private readonly CodeCoverageService $coverageService
    ) {
        parent::__construct();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.code_coverage');
    }

    public function getTitle(): string
    {
        return __('app.pages.code_coverage');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_code_coverage') ?? false;
    }

    public function mount(): void
    {
        $this->loadCoverageData();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_full_coverage')
                ->label(__('app.actions.run_full_coverage'))
                ->icon('heroicon-o-play-circle')
                ->iconPosition(IconPosition::Before)
                ->color('primary')
                ->action(function (): void {
                    $result = $this->coverageService->runCoverage(html: true);

                    if ($result['success']) {
                        $this->loadCoverageData();

                        Notification::make()
                            ->title(__('app.notifications.coverage_generated'))
                            ->body(__('app.notifications.coverage_generated_body'))
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
                ->modalHeading(__('app.modals.run_full_coverage'))
                ->modalDescription(__('app.modals.run_full_coverage_description'))
                ->modalSubmitActionLabel(__('app.actions.run')),

            Action::make('download_html')
                ->label(__('app.actions.download_html_report'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => File::exists(base_path('coverage-html/index.html')))
                ->url(fn (): string => asset('coverage-html/index.html'))
                ->openUrlInNewTab(),

            Action::make('download_xml')
                ->label(__('app.actions.download_xml_report'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => File::exists(base_path('coverage.xml')))
                ->action(fn () => response()->download(base_path('coverage.xml'))),

            Action::make('clear_cache')
                ->label(__('app.actions.clear_cache'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function (): void {
                    $this->coverageService->clearCache();
                    $this->loadCoverageData();

                    Notification::make()
                        ->title(__('app.notifications.cache_cleared'))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),
        ];
    }

    private function loadCoverageData(): void
    {
        $this->stats = $this->coverageService->getCoverageStats();
        $this->categoryStats = $this->coverageService->getCoverageByCategory();
        $this->pcovEnabled = $this->coverageService->isPcovEnabled();
        $this->pcovConfig = $this->coverageService->getPcovConfig();
    }
}
