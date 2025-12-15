<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplateResource\Pages;

use App\Filament\Resources\DocumentTemplateResource;
use App\Filament\Resources\Pages\BaseListRecords;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\Size;
use Override;

final class ListDocumentTemplates extends BaseListRecords
{
    /** @var class-string<DocumentTemplateResource> */
    protected static string $resource = DocumentTemplateResource::class;

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
