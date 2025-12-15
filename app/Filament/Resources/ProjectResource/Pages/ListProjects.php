<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\Pages\BaseListRecords;
use App\Filament\Resources\ProjectResource;
use Filament\Actions\CreateAction;

final class ListProjects extends BaseListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
