<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Filament\Resources\Pages\BaseListRecords;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\Size;
use Override;

final class ListDocuments extends BaseListRecords
{
    /** @var class-string<DocumentResource> */
    protected static string $resource = DocumentResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->size(Size::Small),
        ];
    }
}
