<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorkflowDefinitionResource\Pages;

use App\Filament\Resources\Pages\BaseListRecords;
use App\Filament\Resources\WorkflowDefinitionResource;
use Filament\Actions;

final class ListWorkflowDefinitions extends BaseListRecords
{
    protected static string $resource = WorkflowDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
