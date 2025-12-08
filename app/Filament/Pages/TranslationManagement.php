<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\Translation\TranslationCheckerService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

final class TranslationManagement extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-language';

    protected string $view = 'filament.pages.translation-management';

    protected static string|null|\UnitEnum $navigationGroup = 'System';

    protected static ?int $navigationSort = 100;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.translations');
    }

    public function getTitle(): string
    {
        return __('app.labels.translation_management');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_ui')
                ->label(__('app.actions.open_translation_ui'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url('/translations', shouldOpenInNewTab: true)
                ->color('primary'),

            Action::make('import')
                ->label(__('app.actions.import_translations'))
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function (TranslationCheckerService $service): void {
                    $service->importFromFiles();

                    Notification::make()
                        ->title(__('app.notifications.translations_imported'))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),

            Action::make('export')
                ->label(__('app.actions.export_translations'))
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    Select::make('locale')
                        ->label(__('app.labels.language'))
                        ->options(fn (TranslationCheckerService $service) => $service->getLanguages()->pluck('name', 'code'))
                        ->required(),
                ])
                ->action(function (array $data, TranslationCheckerService $service): void {
                    $service->exportToFiles($data['locale']);

                    Notification::make()
                        ->title(__('app.notifications.translations_exported'))
                        ->success()
                        ->send();
                }),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('manage_translations');
    }
}
