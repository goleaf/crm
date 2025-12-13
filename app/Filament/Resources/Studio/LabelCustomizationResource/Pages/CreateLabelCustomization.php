<?php

declare(strict_types=1);

namespace App\Filament\Resources\Studio\LabelCustomizationResource\Pages;

use App\Filament\Resources\Studio\LabelCustomizationResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateLabelCustomization extends CreateRecord
{
    protected static string $resource = LabelCustomizationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = filament()->getTenant()->id;

        return $data;
    }
}