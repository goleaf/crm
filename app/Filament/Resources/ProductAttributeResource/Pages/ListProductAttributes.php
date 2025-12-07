<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductAttributeResource\Pages;

use App\Filament\Resources\ProductAttributeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Override;

final class ListProductAttributes extends ListRecords
{
    /** @var class-string<ProductAttributeResource> */
    protected static string $resource = ProductAttributeResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->size(Size::Small),
        ];
    }
}
