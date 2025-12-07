<?php

declare(strict_types=1);

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\ListRecords;

final class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;
}
