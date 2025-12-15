<?php

declare(strict_types=1);

namespace App\Filament\Resources\OCRDocumentResource\Pages;

use App\Filament\Resources\OCRDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListOCRDocuments extends ListRecords
{
    protected static string $resource = OCRDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
