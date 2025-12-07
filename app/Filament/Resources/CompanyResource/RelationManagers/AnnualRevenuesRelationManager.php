<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use App\Enums\CreationSource;
use App\Models\CompanyRevenue;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

final class AnnualRevenuesRelationManager extends RelationManager
{
    protected static string $relationship = 'annualRevenues';

    protected static ?string $modelLabel = 'annual revenue';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-banknotes';

    public function form(Schema $schema): Schema
    {
        $defaultCurrency = fn (): string => $this->getOwnerRecord()?->currency_code ?? config('company.default_currency', 'USD');

        return $schema
            ->components([
                TextInput::make('year')
                    ->label('Year')
                    ->required()
                    ->integer()
                    ->minValue(1900)
                    ->maxValue((int) now()->addYear()->year)
                    ->rules([
                        fn (): Rule => Rule::unique('company_revenues', 'year')
                            ->where('company_id', $this->getOwnerRecord()->getKey())
                            ->ignore($this->getRecord()?->getKey()),
                    ])
                    ->helperText('One entry per fiscal year.'),
                TextInput::make('amount')
                    ->label('Annual Revenue')
                    ->numeric()
                    ->required()
                    ->step(0.01)
                    ->minValue(0)
                    ->prefix(fn (Get $get): string => ($get('currency_code') ?? $defaultCurrency()).' '),
                Select::make('currency_code')
                    ->label('Currency')
                    ->options(config('company.currency_codes'))
                    ->default($defaultCurrency)
                    ->required()
                    ->native(false),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('year')
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('year')
                    ->label('Year')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->sortable()
                    ->formatStateUsing(fn (CompanyRevenue $record): string => ($record->currency_code ?? 'USD').' '.number_format((float) $record->amount, 2)),
                TextColumn::make('creator.name')
                    ->label('Recorded By')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Recorded')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->size(Size::Small)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['creation_source'] ??= CreationSource::WEB->value;

                        return $data;
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
