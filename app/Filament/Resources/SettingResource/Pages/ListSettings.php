<?php

declare(strict_types=1);

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\Pages\BaseListRecords;
use App\Filament\Resources\SettingResource;
use Filament\Actions;

final class ListSettings extends BaseListRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
