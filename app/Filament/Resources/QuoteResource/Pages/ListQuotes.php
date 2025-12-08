<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\Pages\BaseListRecords;
use App\Filament\Resources\QuoteResource;

final class ListQuotes extends BaseListRecords
{
    protected static string $resource = QuoteResource::class;
}
