<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExportJobResource\Pages;

use App\Filament\Resources\ExportJobResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateExportJob extends CreateRecord
{
    protected static string $resource = ExportJobResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
