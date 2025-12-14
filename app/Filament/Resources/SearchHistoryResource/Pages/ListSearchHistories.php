<?php

declare(strict_types=1);

namespace App\Filament\Resources\SearchHistoryResource\Pages;

use App\Filament\Resources\SearchHistoryResource;
use Filament\Resources\Pages\ListRecords;

final class ListSearchHistories extends ListRecords
{
    protected static string $resource = SearchHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
