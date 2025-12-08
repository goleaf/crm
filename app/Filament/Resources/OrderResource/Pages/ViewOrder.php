<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;

final class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

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
                    TextEntry::make('id')->label('Order #'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('company.name')->label(__('app.labels.company')),
                    TextEntry::make('contact.name')->label('Contact'),
                    TextEntry::make('opportunity.name')->label('Deal'),
                    TextEntry::make('quote.title')->label('Quote'),
                    TextEntry::make('total')->money(fn (Order $record): string => $record->currency_code ?? 'USD'),
                    TextEntry::make('expected_delivery_date')->date(),
                ]),
                TextEntry::make('notes')->label('Internal Notes')->columnSpanFull()->wrap(),
            ])->columnSpanFull(),
        ]);
    }
}
