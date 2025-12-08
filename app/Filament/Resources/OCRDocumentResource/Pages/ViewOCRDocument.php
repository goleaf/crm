<?php

declare(strict_types=1);

namespace App\Filament\Resources\OCRDocumentResource\Pages;

use App\Filament\Resources\OCRDocumentResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewOCRDocument extends ViewRecord
{
    protected static string $resource = OCRDocumentResource::class;
}
