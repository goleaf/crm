<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\Pages\BaseListRecords;

final class ListOrders extends BaseListRecords
{
    protected static string $resource = OrderResource::class;
}
