<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeFaqResource\Pages;

use App\Filament\Resources\KnowledgeFaqResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Override;

final class EditKnowledgeFaq extends EditRecord
{
    protected static string $resource = KnowledgeFaqResource::class;

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
