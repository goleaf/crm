<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use App\Support\Helpers\StringHelper;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\HtmlString;
use Override;

final class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_sent')
                ->label('Mark as Sent')
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::DRAFT)
                ->action(function (Invoice $record): void {
                    $record->markSent();

                    Notification::make()
                        ->title('Invoice marked as sent')
                        ->success()
                        ->send();
                }),
            Action::make('generate_pdf')
                ->label('Generate PDF')
                ->icon('heroicon-o-printer')
                ->action(function (InvoicePdfService $service, Invoice $record): void {
                    $service->generate($record);

                    Notification::make()
                        ->title('Invoice PDF generated')
                        ->success()
                        ->send();
                }),
            ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Invoice')
                    ->schema([
                        Grid::make()
                            ->columns(3)
                            ->schema([
                                TextEntry::make('number')
                                    ->label('Invoice #'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label())
                                    ->color(fn (InvoiceStatus $state): string => $state->color()),
                                TextEntry::make('currency_code')
                                    ->label('Currency'),
                                TextEntry::make('company.name')
                                    ->label('Company')
                                    ->placeholder('—'),
                                TextEntry::make('contact.name')
                                    ->label('Contact')
                                    ->placeholder('—'),
                                TextEntry::make('opportunity.name')
                                    ->label('Opportunity')
                                    ->placeholder('—'),
                                TextEntry::make('issue_date')
                                    ->label('Issued')
                                    ->date(),
                                TextEntry::make('due_date')
                                    ->label('Due')
                                    ->date(),
                                TextEntry::make('payment_terms')
                                    ->label('Payment Terms')
                                    ->placeholder('—'),
                            ]),
                    ]),
                Section::make('Line Items')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('lineItems')
                            ->table([
                                TableColumn::make('Item'),
                                TableColumn::make('Description'),
                                TableColumn::make('Qty')
                                    ->alignment(Alignment::End),
                                TableColumn::make('Unit Price')
                                    ->alignment(Alignment::End),
                                TableColumn::make('Tax %')
                                    ->alignment(Alignment::End),
                                TableColumn::make('Line Total')
                                    ->alignment(Alignment::End),
                                TableColumn::make('Tax')
                                    ->alignment(Alignment::End),
                            ])
                            ->schema([
                                TextEntry::make('name')
                                    ->weight('bold'),
                                TextEntry::make('description')
                                    ->color('gray')
                                    ->formatStateUsing(
                                        fn (?string $state): HtmlString|string|null => StringHelper::wordWrap(
                                            value: $state,
                                            characters: 80,
                                            break: '<br>',
                                            cutLongWords: true,
                                            emptyPlaceholder: null,
                                        ),
                                    )
                                    ->placeholder('—'),
                                TextEntry::make('quantity')
                                    ->label('Qty')
                                    ->formatStateUsing(fn (string $state): string => number_format((float) $state, 2)),
                                TextEntry::make('unit_price')
                                    ->label('Unit Price')
                                    ->formatStateUsing(fn (string $state, Invoice $record): string => ($record->currency_code ?? 'USD') . ' ' . number_format((float) $state, 2)),
                                TextEntry::make('tax_rate')
                                    ->label('Tax %')
                                    ->formatStateUsing(fn (string $state): string => number_format((float) $state, 2) . ' %'),
                                TextEntry::make('line_total')
                                    ->label('Line Total')
                                    ->formatStateUsing(fn (string $state, Invoice $record): string => ($record->currency_code ?? 'USD') . ' ' . number_format((float) $state, 2)),
                                TextEntry::make('tax_total')
                                    ->label('Tax')
                                    ->formatStateUsing(fn (string $state, Invoice $record): string => ($record->currency_code ?? 'USD') . ' ' . number_format((float) $state, 2)),
                            ])
                            ->columns(3),
                    ]),
                Section::make('Financials')
                    ->schema([
                        Grid::make()
                            ->columns(3)
                            ->schema([
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money(fn (Invoice $record): string => $record->currency_code ?? 'USD'),
                                TextEntry::make('discount_total')
                                    ->label('Discounts')
                                    ->money(fn (Invoice $record): string => $record->currency_code ?? 'USD'),
                                TextEntry::make('tax_total')
                                    ->label('Tax')
                                    ->money(fn (Invoice $record): string => $record->currency_code ?? 'USD'),
                                TextEntry::make('late_fee_amount')
                                    ->label('Late Fees')
                                    ->money(fn (Invoice $record): string => $record->currency_code ?? 'USD'),
                                TextEntry::make('total')
                                    ->label('Total')
                                    ->money(fn (Invoice $record): string => $record->currency_code ?? 'USD'),
                                TextEntry::make('balance_due')
                                    ->label('Balance Due')
                                    ->money(fn (Invoice $record): string => $record->currency_code ?? 'USD'),
                                TextEntry::make('paid_at')
                                    ->label('Paid At')
                                    ->dateTime()
                                    ->placeholder('—'),
                            ]),
                    ]),
                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->columnSpanFull()
                            ->markdown()
                            ->placeholder('No notes provided.'),
                        TextEntry::make('terms')
                            ->columnSpanFull()
                            ->markdown()
                            ->placeholder('No payment terms provided.'),
                    ]),
            ]);
    }
}
