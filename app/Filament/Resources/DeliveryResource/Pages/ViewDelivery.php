<?php

declare(strict_types=1);

namespace App\Filament\Resources\DeliveryResource\Pages;

use App\Filament\Resources\DeliveryResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;

final class ViewDelivery extends ViewRecord
{
    protected static string $resource = DeliveryResource::class;

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
                    TextEntry::make('id')->label('Delivery #'),
                    TextEntry::make('order_id')->label('Order #'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('tracking_number')->label('Tracking'),
                    TextEntry::make('shipped_at')->dateTime(),
                    TextEntry::make('delivered_at')->dateTime(),
                ]),
                TextEntry::make('notes')->label('Notes')->columnSpanFull()->wrap(),
            ])->columnSpanFull(),
        ]);
    }
}
