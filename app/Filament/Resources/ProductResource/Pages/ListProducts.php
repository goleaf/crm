<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\Pages\BaseListRecords;
use App\Filament\Resources\ProductResource;

final class ListProducts extends BaseListRecords
{
    protected static string $resource = ProductResource::class;
}
