<?php

declare(strict_types=1);

namespace App\Filament\Resources\OCRDocumentResource\Pages;

use App\Filament\Resources\OCRDocumentResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateOCRDocument extends CreateRecord
{
    protected static string $resource = OCRDocumentResource::class;
}
