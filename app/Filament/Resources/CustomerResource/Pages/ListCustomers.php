<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\Pages\BaseListRecords;

final class ListCustomers extends BaseListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
