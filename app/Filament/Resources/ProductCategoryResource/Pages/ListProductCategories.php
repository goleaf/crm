<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductCategoryResource\Pages;

use App\Filament\Resources\Pages\BaseListRecords;
use App\Filament\Resources\ProductCategoryResource;

final class ListProductCategories extends BaseListRecords
{
    protected static string $resource = ProductCategoryResource::class;
}
