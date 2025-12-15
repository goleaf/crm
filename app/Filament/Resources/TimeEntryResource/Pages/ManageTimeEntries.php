<?php

declare(strict_types=1);

namespace App\Filament\Resources\TimeEntryResource\Pages;

use App\Filament\Resources\TimeEntryResource;
use App\Models\TimeEntry;
use App\Services\TimeManagement\TimeEntryService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Size;

final class ManageTimeEntries extends ManageRecords
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->size(Size::Small)
                ->databaseTransaction()
                ->using(function (array $data): TimeEntry {
                    $actor = auth()->user();

                    return resolve(TimeEntryService::class)->createTimeEntry($data, $actor);
                }),
        ];
    }
}

