<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\Pages\BaseListRecords;
use Filament\Actions\CreateAction;
use Override;
use Relaticle\SystemAdmin\Filament\Resources\OpportunityResource;

final class ListOpportunities extends BaseListRecords
{
    protected static string $resource = OpportunityResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
