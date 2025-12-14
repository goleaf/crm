<?php

declare(strict_types=1);

namespace App\Filament\Resources\BulkOperationResource\Pages;

use App\Filament\Resources\BulkOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListBulkOperations extends ListRecords
{
    protected static string $resource = BulkOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cleanup')
                ->label(__('app.actions.cleanup_completed'))
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->action(function (): void {
                    $deleted = \App\Models\BulkOperation::whereIn('status', [
                        \App\Enums\BulkOperationStatus::COMPLETED,
                        \App\Enums\BulkOperationStatus::FAILED,
                        \App\Enums\BulkOperationStatus::CANCELLED,
                    ])
                        ->where('created_at', '<', now()->subDays(7))
                        ->delete();

                    \Filament\Notifications\Notification::make()
                        ->title(__('app.notifications.bulk_operations_cleaned'))
                        ->body(__('app.notifications.bulk_operations_cleaned_body', ['count' => $deleted]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading(__('app.modals.cleanup_bulk_operations'))
                ->modalDescription(__('app.modals.cleanup_bulk_operations_description')),
        ];
    }
}
