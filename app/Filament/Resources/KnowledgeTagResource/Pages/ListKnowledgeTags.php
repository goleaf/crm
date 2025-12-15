<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeTagResource\Pages;

use App\Filament\Resources\KnowledgeTagResource;
use App\Filament\Resources\Pages\BaseListRecords;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\Size;
use Override;

final class ListKnowledgeTags extends BaseListRecords
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
