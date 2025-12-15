<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Resources\NoteResource\Pages;

use App\Filament\Resources\Pages\BaseListRecords;
use Filament\Actions\CreateAction;
use Override;
use Relaticle\SystemAdmin\Filament\Resources\NoteResource;

final class ListNotes extends BaseListRecords
{
    protected static string $resource = NoteResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
