<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\Config\ConfigCheckerService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

final class ConfigChecker extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-shield-check';

    protected string $view = 'filament.pages.config-checker';

    protected static ?int $navigationSort = 101;

    protected static string|null|\UnitEnum $navigationGroup = 'System';

    public array $checkResult = [];

    public function mount(ConfigCheckerService $service): void
    {
        $this->checkResult = $service->getCachedCheck();
    }

    public static function getNavigationLabel(): string
    {
        return 'Config Checker';
    }

    public function getTitle(): string
    {
        return 'Configuration Health';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Run Check')
                ->action(function (ConfigCheckerService $service): void {
                    $service->clearCache();
                    $this->checkResult = $service->check();

                    Notification::make()
                        ->title('Config check completed')
                        ->success()
                        ->send();
                }),
        ];
    }
}
