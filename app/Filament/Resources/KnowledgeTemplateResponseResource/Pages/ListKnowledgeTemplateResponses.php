<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeTemplateResponseResource\Pages;

use App\Filament\Resources\KnowledgeTemplateResponseResource;
use App\Filament\Resources\Pages\BaseListRecords;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\Size;
use Override;

final class ListKnowledgeTemplateResponses extends BaseListRecords
{
    protected static string $resource = KnowledgeTemplateResponseResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
        ];
    }
}
