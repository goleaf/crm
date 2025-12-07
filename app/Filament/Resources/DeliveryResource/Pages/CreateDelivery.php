<?php

declare(strict_types=1);

namespace App\Filament\Resources\DeliveryResource\Pages;

use App\Filament\Resources\DeliveryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateDelivery extends CreateRecord
{
    protected static string $resource = DeliveryResource::class;
}
