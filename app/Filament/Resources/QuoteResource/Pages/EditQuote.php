<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use Filament\Resources\Pages\EditRecord;

final class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;
}
