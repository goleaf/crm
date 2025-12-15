<?php

declare(strict_types=1);

namespace App\Filament\Resources\DataIntegrityCheckResource\Pages;

use App\Filament\Resources\DataIntegrityCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewDataIntegrityCheck extends ViewRecord
{
    protected static string $resource = DataIntegrityCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('run_check')
                ->label(__('app.actions.run_check'))
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn (): bool => $this->record->isPending())
                ->requiresConfirmation()
                ->action(function (): void {
                    $dataQualityService = resolve(\App\Services\DataQuality\DataQualityService::class);
                    $dataQualityService->runIntegrityCheck(
                        $this->record->type,
                        $this->record->target_model,
                        $this->record->check_parameters ?? [],
                        $this->record->team_id,
                    );

                    \Filament\Notifications\Notification::make()
                        ->title(__('app.notifications.integrity_check_started'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'started_at']);
                }),
        ];
    }
}
