<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductAttributeResource\Pages;

use App\Filament\Resources\ProductAttributeResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

final class CreateProductAttribute extends CreateRecord
{
    /** @var class-string<ProductAttributeResource> */
    protected static string $resource = ProductAttributeResource::class;

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
