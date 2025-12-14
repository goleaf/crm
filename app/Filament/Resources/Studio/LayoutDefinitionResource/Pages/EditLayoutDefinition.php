<?php

declare(strict_types=1);

namespace App\Filament\Resources\Studio\LayoutDefinitionResource\Pages;

use App\Filament\Resources\Studio\LayoutDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditLayoutDefinition extends EditRecord
{
    protected static string $resource = LayoutDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => ! $this->record->system_defined),
        ];
    }
}
