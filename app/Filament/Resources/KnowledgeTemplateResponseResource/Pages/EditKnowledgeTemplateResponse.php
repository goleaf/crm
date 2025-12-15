<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeTemplateResponseResource\Pages;

use App\Filament\Resources\KnowledgeTemplateResponseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Override;

final class EditKnowledgeTemplateResponse extends EditRecord
{
    protected static string $resource = KnowledgeTemplateResponseResource::class;

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
