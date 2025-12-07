<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeTagResource\Pages;

use App\Filament\Resources\KnowledgeTagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Override;

final class ListKnowledgeTags extends ListRecords
{
    protected static string $resource = KnowledgeTagResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
        ];
    }
}
