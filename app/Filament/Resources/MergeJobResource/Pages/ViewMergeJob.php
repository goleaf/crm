<?php

declare(strict_types=1);

namespace App\Filament\Resources\MergeJobResource\Pages;

use App\Filament\Resources\MergeJobResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewMergeJob extends ViewRecord
{
    protected static string $resource = MergeJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('process')
                ->label(__('app.actions.process'))
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn (): bool => $this->record->isPending())
                ->requiresConfirmation()
                ->action(function (): void {
                    $dataQualityService = resolve(\App\Services\DataQuality\DataQualityService::class);
                    $success = $dataQualityService->processMergeJob($this->record);

                    if ($success) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('app.notifications.merge_job_processed'))
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'processed_at', 'transferred_relationships']);
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title(__('app.notifications.merge_job_failed'))
                            ->danger()
                            ->send();

                        $this->refreshFormData(['status', 'error_message']);
                    }
                }),
        ];
    }
}
