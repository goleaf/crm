<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeFaqResource\Pages;

use App\Filament\Resources\KnowledgeFaqResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Override;

final class ListKnowledgeFaqs extends ListRecords
{
    protected static string $resource = KnowledgeFaqResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
        ];
    }
}
