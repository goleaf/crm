<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Enums\InvoicePaymentStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-credit-card';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->step(0.01),
                \Filament\Forms\Components\Select::make('currency_code')
                    ->label('Currency')
                    ->options(config('company.currency_codes'))
                    ->default(config('company.default_currency', 'USD'))
                    ->native(false),
                \Filament\Forms\Components\DateTimePicker::make('paid_at')
                    ->label('Paid At')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('method')
                    ->label('Method'),
                \Filament\Forms\Components\TextInput::make('reference')
                    ->label('Reference')
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('status')
                    ->options(InvoicePaymentStatus::options())
                    ->enum(InvoicePaymentStatus::class)
                    ->native(false)
                    ->required(),
                \Filament\Forms\Components\Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('amount')
                    ->money(fn (\App\Models\InvoicePayment $record): string => $record->currency_code ?? $record->invoice->currency_code ?? 'USD')
                    ->label('Amount')
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('method')
                    ->label('Method')
                    ->searchable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => InvoicePaymentStatus::COMPLETED->value,
                        'gray' => InvoicePaymentStatus::PENDING->value,
                        'danger' => InvoicePaymentStatus::FAILED->value,
                        'warning' => InvoicePaymentStatus::REFUNDED->value,
                    ])
                    ->formatStateUsing(fn (InvoicePaymentStatus $state): string => $state->label()),
            ])
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
