<?php

declare(strict_types=1);

namespace App\Filament\Resources\Studio\FieldDependencyResource\Pages;

use App\Filament\Resources\Studio\FieldDependencyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditFieldDependency extends EditRecord
{
    protected static string $resource = FieldDependencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}