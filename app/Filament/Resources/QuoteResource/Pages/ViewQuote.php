<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Enums\QuoteDiscountType;
use App\Enums\QuoteStatus;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\QuoteResource;
use App\Models\Order;
use App\Models\Quote;
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

final class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('accept')
                ->label('Accept')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (Quote $record): bool => $record->status !== QuoteStatus::ACCEPTED)
                ->action(function (Quote $record): void {
                    if ($record->isExpired()) {
                        Notification::make()
                            ->title('Quote is expired')
                            ->body('Extend the expiration date before accepting.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $record->markAccepted();

                    Notification::make()
                        ->title('Quote accepted')
                        ->success()
                        ->send();
                }),
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn (Quote $record): bool => $record->status !== QuoteStatus::REJECTED)
                ->form([
                    \Filament\Forms\Components\Textarea::make('note')
                        ->label('Reason')
                        ->rows(3),
                ])
                ->action(function (Quote $record, array $data): void {
                    $record->markRejected($data['note'] ?? null);

                    Notification::make()
                        ->title('Quote rejected')
                        ->warning()
                        ->send();
                }),
            Action::make('create_order')
                ->label('Create Order')
                ->icon('heroicon-o-briefcase')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (Quote $record): bool => $record->status === QuoteStatus::ACCEPTED)
                ->action(function (Quote $record): void {
                    /** @var Order $order */
                    $order = Order::query()->create([
                        'team_id' => $record->team_id,
                        'company_id' => $record->company_id,
                        'contact_id' => $record->contact_id,
                        'opportunity_id' => $record->opportunity_id,
                        'quote_id' => $record->getKey(),
                        'currency_code' => $record->currency_code,
                    ]);

                    $record->lineItems()
                        ->orderBy('sort_order')
                        ->get()
                        ->each(function ($item) use ($order): void {
                            $order->lineItems()->create([
                                'team_id' => $order->team_id,
                                'name' => $item->name,
                                'description' => $item->description,
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'tax_rate' => $item->tax_rate,
                                'sort_order' => $item->sort_order,
                            ]);
                        });

                    $order->syncFinancials();

                    Notification::make()
                        ->title('Order created from quote')
                        ->success()
                        ->send();

                    $this->redirect(OrderResource::getUrl('edit', [$order]));
                }),
            ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Quote')
                ->schema([
                    Grid::make()
                        ->columns(3)
                        ->schema([
                            TextEntry::make('title')
                                ->label('Subject'),
                            TextEntry::make('status')
                                ->badge()
                                ->formatStateUsing(function (QuoteStatus|string|null $state): string {
                                    $resolved = $state instanceof QuoteStatus ? $state : (is_string($state) ? QuoteStatus::tryFrom($state) : null);

                                    return $resolved?->getLabel() ?? '—';
                                })
                                ->color(function (QuoteStatus|string|null $state): string {
                                    $resolved = $state instanceof QuoteStatus ? $state : (is_string($state) ? QuoteStatus::tryFrom($state) : null);

                                    return $resolved?->color() ?? 'gray';
                                }),
                            TextEntry::make('owner.name')
                                ->label('Sales Owner')
                                ->placeholder('—'),
                            TextEntry::make('company.name')
                                ->label(__('app.labels.company'))
                                ->placeholder('—'),
                            TextEntry::make('contact.name')
                                ->label('Person')
                                ->placeholder('—'),
                            TextEntry::make('lead.name')
                                ->label('Lead')
                                ->placeholder('—'),
                            TextEntry::make('opportunity.name')
                                ->label('Deal')
                                ->placeholder('—'),
                            TextEntry::make('valid_until')
                                ->label('Expires')
                                ->date(),
                            TextEntry::make('currency_code')
                                ->label('Currency'),
                        ]),
                    TextEntry::make('description')
                        ->label('Description')
                        ->columnSpanFull()
                        ->formatStateUsing(
                            fn (?string $state): HtmlString|string|null => StringHelper::wordWrap(
                                value: $state,
                                characters: 120,
                                break: '<br>',
                                cutLongWords: true,
                                emptyPlaceholder: null,
                            ),
                        )
                        ->placeholder('No description provided.'),
                ])
                ->columnSpanFull(),
            Section::make('Addresses')
                ->collapsible()
                ->schema([
                    Grid::make()
                        ->columns(2)
                        ->schema([
                            TextEntry::make('billing_address')
                                ->label('Billing Address')
                                ->formatStateUsing(fn (?array $state): string => $this->formatAddress($state))
                                ->placeholder('—'),
                            TextEntry::make('shipping_address')
                                ->label('Shipping Address')
                                ->formatStateUsing(fn (?array $state): string => $this->formatAddress($state))
                                ->placeholder('—'),
                        ]),
                ])
                ->columnSpanFull(),
            Section::make('Line Items')
                ->collapsible()
                ->schema([
                    RepeatableEntry::make('lineItems')
                        ->table([
                            TableColumn::make('Item'),
                            TableColumn::make('Qty')
                                ->alignment(Alignment::End),
                            TableColumn::make('Price')
                                ->alignment(Alignment::End),
                            TableColumn::make('Discount')
                                ->alignment(Alignment::End),
                            TableColumn::make('Type')
                                ->alignment(Alignment::End),
                            TableColumn::make('Tax %')
                                ->alignment(Alignment::End),
                            TableColumn::make('Line Total')
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
                                ->label('Price')
                                ->formatStateUsing(fn (string $state, Quote $record): string => ($record->currency_code ?? 'USD') . ' ' . number_format((float) $state, 2)),
                            TextEntry::make('discount_value')
                                ->label('Discount')
                                ->formatStateUsing(fn (string $state): string => number_format((float) $state, 2)),
                            TextEntry::make('discount_type')
                                ->label('Type')
                                ->formatStateUsing(fn (QuoteDiscountType|string|null $state): string => $state instanceof QuoteDiscountType ? $state->getLabel() : QuoteDiscountType::tryFrom((string) $state)?->getLabel() ?? (string) $state),
                            TextEntry::make('tax_rate')
                                ->label('Tax %')
                                ->formatStateUsing(fn (string $state): string => number_format((float) $state, 2) . ' %'),
                            TextEntry::make('line_total')
                                ->label('Line Total')
                                ->formatStateUsing(fn (string $state, Quote $record): string => ($record->currency_code ?? 'USD') . ' ' . number_format((float) $state, 2)),
                        ])
                        ->columns(3),
                ])
                ->columnSpanFull(),
            Section::make('Financials')
                ->schema([
                    Grid::make()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('subtotal')
                                ->label('Subtotal')
                                ->money(fn (Quote $record): string => $record->currency_code ?? 'USD'),
                            TextEntry::make('discount_total')
                                ->label('Discounts')
                                ->money(fn (Quote $record): string => $record->currency_code ?? 'USD'),
                            TextEntry::make('tax_total')
                                ->label('Tax')
                                ->money(fn (Quote $record): string => $record->currency_code ?? 'USD'),
                            TextEntry::make('total')
                                ->label('Total')
                                ->money(fn (Quote $record): string => $record->currency_code ?? 'USD'),
                        ]),
                ])
                ->columnSpanFull(),
            Section::make('Decision')
                ->schema([
                    TextEntry::make('decision_note')
                        ->label('Decision Notes')
                        ->columnSpanFull()
                        ->wrap()
                        ->placeholder('No decision captured yet.'),
                ])
                ->columnSpanFull(),
        ]);
    }

    /**
     * @param array<string, string|null>|null $address
     */
    private function formatAddress(?array $address): string
    {
        if ($address === null || $address === []) {
            return '—';
        }

        $parts = collect([
            $address['line1'] ?? null,
            $address['line2'] ?? null,
            $address['city'] ?? null,
            $address['state'] ?? null,
            $address['postal_code'] ?? null,
            $address['country_code'] ?? null,
        ])->filter()->implode(', ');

        return $parts !== '' ? $parts : '—';
    }
}
