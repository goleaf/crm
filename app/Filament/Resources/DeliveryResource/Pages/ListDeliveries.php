<?php

declare(strict_types=1);

namespace App\Filament\Resources\DeliveryResource\Pages;

use App\Filament\Resources\DeliveryResource;
use App\Filament\Resources\Pages\BaseListRecords;

final class ListDeliveries extends BaseListRecords
{
    protected static string $resource = DeliveryResource::class;
}
