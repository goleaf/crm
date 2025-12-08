<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportCaseResource\Pages;

use App\Filament\Resources\Pages\BaseListRecords;
use App\Filament\Resources\SupportCaseResource;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\Size;
use Override;
use Relaticle\CustomFields\Concerns\InteractsWithCustomFields;

final class ListSupportCases extends BaseListRecords
{
    use InteractsWithCustomFields;

    protected static string $resource = SupportCaseResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
        ];
    }
}
