<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeTagResource\Pages;

use App\Filament\Resources\KnowledgeTagResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateKnowledgeTag extends CreateRecord
{
    protected static string $resource = KnowledgeTagResource::class;
}
