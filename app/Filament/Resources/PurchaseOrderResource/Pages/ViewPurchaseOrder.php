<?php

declare(strict_types=1);

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;

final class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Grid::make()->columns(2)->schema([
                    TextEntry::make('id')->label('PO #'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('supplier_name')->label('Supplier'),
                    TextEntry::make('total')->money(fn ($record): string => $record->currency_code ?? 'USD'),
                ]),
                TextEntry::make('notes')->label('Notes')->columnSpanFull()->wrap(),
            ])->columnSpanFull(),
        ]);
    }
}
