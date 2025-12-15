<?php

declare(strict_types=1);

namespace App\Filament\Resources\Studio\LabelCustomizationResource\Pages;

use App\Filament\Resources\Studio\LabelCustomizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListLabelCustomizations extends ListRecords
{
    protected static string $resource = LabelCustomizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
