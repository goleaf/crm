<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Resources\PeopleResource\Pages;

use App\Filament\Resources\Pages\BaseListRecords;
use Filament\Actions\CreateAction;
use Override;
use Relaticle\SystemAdmin\Filament\Resources\PeopleResource;

final class ListPeople extends BaseListRecords
{
    protected static string $resource = PeopleResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
