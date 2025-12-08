<?php

declare(strict_types=1);

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\Pages\BaseListRecords;
use App\Filament\Resources\PurchaseOrderResource;

final class ListPurchaseOrders extends BaseListRecords
{
    protected static string $resource = PurchaseOrderResource::class;
}
