<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplateResource\Pages;

use App\Filament\Resources\DocumentTemplateResource;
use Filament\Resources\Pages\EditRecord;

final class EditDocumentTemplate extends EditRecord
{
    protected static string $resource = DocumentTemplateResource::class;
}
