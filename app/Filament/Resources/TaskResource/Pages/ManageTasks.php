<?php

declare(strict_types=1);

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Size;
use Illuminate\Support\Facades\Log;
use Relaticle\CustomFields\Concerns\InteractsWithCustomFields;

final class ManageTasks extends ManageRecords
{
    use InteractsWithCustomFields;

    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->size(Size::Small)
                ->slideOver()
                ->databaseTransaction()
                ->after(function (CreateAction $action): void {
                    $record = $action->getRecord();

                    if (! $record instanceof Task) {
                        return;
                    }

                    try {
                        TaskResource::notifyNewAssignees($record);
                    } catch (\Throwable $e) {
                        Log::error('Task creation notification failed', [
                            'task_id' => $record->getKey(),
                            'error' => $e->getMessage(),
                        ]);

                        throw $e;
                    }
                }),
        ];
    }
}
