<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExportJobResource\Pages;

use App\Filament\Resources\ExportJobResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditExportJob extends EditRecord
{
    protected static string $resource = ExportJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
