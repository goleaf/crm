<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Enums\QuoteStatus;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\QuoteResource;
use App\Models\Order;
use App\Models\Quote;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;

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
                        'line_items' => $record->line_items,
                    ]);

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
            Section::make()->schema([
                Grid::make()->columns(2)->schema([
                    TextEntry::make('title'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (Quote $record): string => $record->status?->color() ?? 'gray')
                        ->formatStateUsing(fn (Quote $record): string => $record->status?->getLabel() ?? ''),
                    TextEntry::make('company.name')->label(__('app.labels.company')),
                    TextEntry::make('contact.name')->label('Contact'),
                    TextEntry::make('opportunity.name')->label('Deal'),
                    TextEntry::make('valid_until')->date(),
                    TextEntry::make('total')->money(fn (Quote $record): string => $record->currency_code ?? 'USD'),
                ]),
                TextEntry::make('decision_note')->label('Decision Notes')->columnSpanFull()->wrap(),
            ])->columnSpanFull(),
        ]);
    }
}
