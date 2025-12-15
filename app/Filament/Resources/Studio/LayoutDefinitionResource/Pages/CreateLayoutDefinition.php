<?php

declare(strict_types=1);

namespace App\Filament\Resources\Studio\LayoutDefinitionResource\Pages;

use App\Filament\Resources\Studio\LayoutDefinitionResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateLayoutDefinition extends CreateRecord
{
    protected static string $resource = LayoutDefinitionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = filament()->getTenant()->id;

        return $data;
    }
}
