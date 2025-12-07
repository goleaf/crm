<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorkflowDefinitionResource\Pages;

use App\Filament\Resources\WorkflowDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListWorkflowDefinitions extends ListRecords
{
    protected static string $resource = WorkflowDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
