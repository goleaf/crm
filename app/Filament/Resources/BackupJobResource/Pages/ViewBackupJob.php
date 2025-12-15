<?php

declare(strict_types=1);

namespace App\Filament\Resources\BackupJobResource\Pages;

use App\Filament\Resources\BackupJobResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewBackupJob extends ViewRecord
{
    protected static string $resource = BackupJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label(__('app.actions.download'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->visible(fn (): bool => $this->record->isCompleted() && $this->record->backup_path && file_exists($this->record->backup_path))
                ->action(fn () => response()->download($this->record->backup_path, basename($this->record->backup_path))),

            Actions\Action::make('restore')
                ->label(__('app.actions.restore'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->visible(fn (): bool => $this->record->isCompleted())
                ->requiresConfirmation()
                ->modalHeading(__('app.modals.restore_backup'))
                ->modalDescription(__('app.modals.restore_backup_description'))
                ->action(function (): void {
                    $backupService = resolve(\App\Services\DataQuality\BackupService::class);
                    $success = $backupService->restore($this->record);

                    if ($success) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('app.notifications.backup_restored'))
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title(__('app.notifications.backup_restore_failed'))
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('verify')
                ->label(__('app.actions.verify'))
                ->icon('heroicon-o-shield-check')
                ->color('info')
                ->visible(fn (): bool => $this->record->isCompleted())
                ->action(function (): void {
                    $backupService = resolve(\App\Services\DataQuality\BackupService::class);
                    $results = $backupService->verifyBackup($this->record->backup_path, $this->record);

                    $this->record->update(['verification_results' => $results]);

                    \Filament\Notifications\Notification::make()
                        ->title(__('app.notifications.backup_verified'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['verification_results']);
                }),
        ];
    }
}
