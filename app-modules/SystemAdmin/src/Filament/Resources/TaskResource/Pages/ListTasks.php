<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\Pages\BaseListRecords;
use Filament\Actions\CreateAction;
use Override;
use Relaticle\SystemAdmin\Filament\Resources\TaskResource;

final class ListTasks extends BaseListRecords
{
    protected static string $resource = TaskResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
