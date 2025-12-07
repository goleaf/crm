<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorkflowDefinitionResource\Pages;

use App\Filament\Resources\WorkflowDefinitionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateWorkflowDefinition extends CreateRecord
{
    protected static string $resource = WorkflowDefinitionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()->currentTeam->id;
        $data['creator_id'] = auth()->id();
        $data['slug'] = Str::slug($data['name']);
        $data['version'] = 1;

        return $data;
    }
}
