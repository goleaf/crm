<?php

declare(strict_types=1);

namespace App\Filament\Resources\DataIntegrityCheckResource\Pages;

use App\Filament\Resources\DataIntegrityCheckResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateDataIntegrityCheck extends CreateRecord
{
    protected static string $resource = DataIntegrityCheckResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()->currentTeam->id;
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        // Automatically run the check after creation
        $dataQualityService = resolve(\App\Services\DataQuality\DataQualityService::class);
        $dataQualityService->runIntegrityCheck(
            $this->record->type,
            $this->record->target_model,
            $this->record->check_parameters ?? [],
            $this->record->team_id,
        );
    }
}
