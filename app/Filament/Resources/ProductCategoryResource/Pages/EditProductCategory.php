<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductCategoryResource\Pages;

use App\Filament\Resources\ProductCategoryResource;
use Filament\Resources\Pages\EditRecord;

final class EditProductCategory extends EditRecord
{
    protected static string $resource = ProductCategoryResource::class;
}
