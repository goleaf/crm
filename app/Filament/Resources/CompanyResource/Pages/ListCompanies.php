<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\Pages\BaseListRecords;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\Size;
use Override;
use Relaticle\CustomFields\Concerns\InteractsWithCustomFields;

final class ListCompanies extends BaseListRecords
{
    use InteractsWithCustomFields;

    /** @var class-string<CompanyResource> */
    protected static string $resource = CompanyResource::class;

    /**
     * Get the actions available on the resource index header.
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
        ];
    }
}
