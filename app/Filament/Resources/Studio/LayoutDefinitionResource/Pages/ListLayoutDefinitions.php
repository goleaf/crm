<?php

declare(strict_types=1);

namespace App\Filament\Resources\Studio\LayoutDefinitionResource\Pages;

use App\Filament\Resources\Studio\LayoutDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListLayoutDefinitions extends ListRecords
{
    protected static string $resource = LayoutDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
