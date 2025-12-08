<?php

declare(strict_types=1);

namespace App\Filament\Resources\OCRDocumentResource\Pages;

use App\Filament\Resources\OCRDocumentResource;
use Filament\Resources\Pages\EditRecord;

final class EditOCRDocument extends EditRecord
{
    protected static string $resource = OCRDocumentResource::class;
}
