<?php

declare(strict_types=1);

namespace App\Filament\Resources\DataIntegrityCheckResource\Pages;

use App\Filament\Resources\DataIntegrityCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListDataIntegrityChecks extends ListRecords
{
    protected static string $resource = DataIntegrityCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
