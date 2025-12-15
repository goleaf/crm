<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Exports\LeadExporter;
use App\Filament\Imports\LeadImporter;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\Pages\BaseListRecords;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Support\Enums\Size;
use Relaticle\CustomFields\Concerns\InteractsWithCustomFields;

final class ListLeads extends BaseListRecords
{
    use InteractsWithCustomFields;

    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ImportAction::make()->importer(LeadImporter::class),
                ExportAction::make()->exporter(LeadExporter::class),
            ])
                ->icon('heroicon-o-arrows-up-down')
                ->color('gray')
                ->button()
                ->label('Import / Export')
                ->size(Size::Small),
            CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
        ];
    }
}
