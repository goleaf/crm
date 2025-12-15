<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeFaqResource\Pages;

use App\Filament\Resources\KnowledgeFaqResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateKnowledgeFaq extends CreateRecord
{
    protected static string $resource = KnowledgeFaqResource::class;
}
