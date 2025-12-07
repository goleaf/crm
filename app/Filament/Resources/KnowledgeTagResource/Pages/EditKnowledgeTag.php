<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeTagResource\Pages;

use App\Filament\Resources\KnowledgeTagResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Override;

final class EditKnowledgeTag extends EditRecord
{
    protected static string $resource = KnowledgeTagResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            RestoreAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
        ];
    }
}
