<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;
}
