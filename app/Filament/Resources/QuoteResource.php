<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\QuoteStatus;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\QuoteResource\Pages\CreateQuote;
use App\Filament\Resources\QuoteResource\Pages\EditQuote;
use App\Filament\Resources\QuoteResource\Pages\ListQuotes;
use App\Filament\Resources\QuoteResource\Pages\ViewQuote;
use App\Filament\Resources\QuoteResource\RelationManagers\NotesRelationManager;
use App\Filament\Resources\QuoteResource\RelationManagers\TasksRelationManager;
use App\Models\Product;
use App\Models\Quote;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Quote Title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Select::make('company_id')
                ->relationship('company', 'name')
                ->searchable()
                ->preload()
                ->label(__('app.labels.company'))
                ->columnSpan(2),
            Select::make('contact_id')
                ->relationship('contact', 'name')
                ->searchable()
                ->preload()
                ->label('Contact')
                ->columnSpan(2),
            Select::make('opportunity_id')
                ->relationship('opportunity', 'name')
                ->searchable()
                ->preload()
                ->label('Related Deal')
                ->columnSpan(2),
            Select::make('status')
                ->options(QuoteStatus::options())
                ->default(QuoteStatus::DRAFT)
                ->required()
                ->native(false)
                ->columnSpan(2),
            TextInput::make('currency_code')
                ->label('Currency')
                ->maxLength(3)
                ->default('USD')
                ->columnSpan(1),
            DatePicker::make('valid_until')
                ->label('Valid Until')
                ->native(false)
                ->columnSpan(2),
            Repeater::make('line_items')
                ->label('Line Items')
                ->table([
                    TableColumn::make('Product'),
                    TableColumn::make('Name / Description')
                        ->markAsRequired(),
                    TableColumn::make('Qty')
                        ->markAsRequired()
                        ->alignment(Alignment::End),
                    TableColumn::make('Unit price')
                        ->markAsRequired()
                        ->alignment(Alignment::End),
                    TableColumn::make('Tax %')
                        ->alignment(Alignment::End),
                ])
                ->compact()
                ->schema([
                    Select::make('product_id')
                        ->label('Product')
                        ->options(fn (): Collection => Product::query()->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->columnSpan(2),
                    TextInput::make('name')
                        ->label('Name / Description')
                        ->required()
                        ->columnSpan(3),
                    TextInput::make('quantity')
                        ->label('Qty')
                        ->numeric()
                        ->default(1)
                        ->minValue(0)
                        ->columnSpan(1),
                    TextInput::make('unit_price')
                        ->label('Unit price')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->columnSpan(2),
                    TextInput::make('tax_rate')
                        ->label('Tax %')
                        ->numeric()
                        ->default(0)
                        ->suffix('%')
                        ->minValue(0)
                        ->columnSpan(2),
                ])
                ->addActionLabel('Add line')
                ->default([])
                ->columnSpanFull(),
            Textarea::make('decision_note')
                ->label('Decision Notes')
                ->maxLength(1000)
                ->columnSpanFull(),
        ])->columns(6);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label(__('app.labels.company'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('contact.name')
                    ->label('Contact')
                    ->toggleable(),
                BadgeColumn::make('status')
                    ->colors([
                        'gray' => QuoteStatus::DRAFT->value,
                        'primary' => QuoteStatus::SENT->value,
                        'success' => QuoteStatus::ACCEPTED->value,
                        'danger' => QuoteStatus::REJECTED->value,
                    ])
                    ->formatStateUsing(fn (QuoteStatus|string|null $state): string => $state instanceof QuoteStatus ? $state->getLabel() : (string) $state)
                    ->sortable(),
                TextColumn::make('total')
                    ->money(fn (Quote $record): string => $record->currency_code ?? 'USD')
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
            TasksRelationManager::class,
            NotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotes::route('/'),
            'create' => CreateQuote::route('/create'),
            'view' => ViewQuote::route('/{record}'),
            'edit' => EditQuote::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Quote>
     */
    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->when(
                $tenant,
                fn (Builder $query): Builder => $query->whereBelongsTo($tenant, 'team')
            );
    }
}
