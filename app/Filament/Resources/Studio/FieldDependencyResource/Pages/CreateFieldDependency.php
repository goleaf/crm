<?php

declare(strict_types=1);

namespace App\Filament\Resources\Studio\FieldDependencyResource\Pages;

use App\Filament\Resources\Studio\FieldDependencyResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFieldDependency extends CreateRecord
{
    protected static string $resource = FieldDependencyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = filament()->getTenant()->id;

        return $data;
    }
}
