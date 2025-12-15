<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\Pages\BaseListRecords;
use Filament\Actions\CreateAction;
use Override;
use Relaticle\SystemAdmin\Filament\Resources\UserResource;

final class ListUsers extends BaseListRecords
{
    protected static string $resource = UserResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
