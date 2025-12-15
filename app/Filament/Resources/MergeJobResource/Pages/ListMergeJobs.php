<?php

declare(strict_types=1);

namespace App\Filament\Resources\MergeJobResource\Pages;

use App\Filament\Resources\MergeJobResource;
use Filament\Resources\Pages\ListRecords;

final class ListMergeJobs extends ListRecords
{
    protected static string $resource = MergeJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
