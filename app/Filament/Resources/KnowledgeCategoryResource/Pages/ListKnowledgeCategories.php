<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeCategoryResource\Pages;

use App\Filament\Resources\KnowledgeCategoryResource;
use App\Filament\Resources\Pages\BaseListRecords;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\Size;
use Override;

final class ListKnowledgeCategories extends BaseListRecords
{
    protected static string $resource = KnowledgeCategoryResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
        ];
    }
}
