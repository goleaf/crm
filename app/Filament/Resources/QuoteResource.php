<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\QuoteDiscountType;
use App\Enums\QuoteStatus;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\QuoteResource\Pages\CreateQuote;
use App\Filament\Resources\QuoteResource\Pages\EditQuote;
use App\Filament\Resources\QuoteResource\Pages\ListQuotes;
use App\Filament\Resources\QuoteResource\Pages\ViewQuote;
use App\Filament\Resources\QuoteResource\RelationManagers\NotesRelationManager;
use App\Filament\Resources\QuoteResource\RelationManagers\StatusHistoriesRelationManager;
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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
            Section::make('Quote Information')
                ->schema([
                    Grid::make()
                        ->columns(6)
                        ->schema([
                            TextInput::make('title')
                                ->label('Subject')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(3),
                            Select::make('status')
                                ->options(QuoteStatus::options())
                                ->default(QuoteStatus::DRAFT)
                                ->required()
                                ->native(false)
                                ->columnSpan(3),
                            Textarea::make('description')
                                ->label('Description')
                                ->rows(3)
                                ->columnSpanFull(),
                            Select::make('owner_id')
                                ->relationship('owner', 'name')
                                ->label('Sales Owner')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default(fn () => auth()->id())
                                ->columnSpan(2),
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
                                ->label('Person')
                                ->columnSpan(2),
                            Select::make('lead_id')
                                ->relationship('lead', 'name')
                                ->searchable()
                                ->preload()
                                ->label('Link to Lead')
                                ->columnSpan(2),
                            Select::make('opportunity_id')
                                ->relationship('opportunity', 'name')
                                ->searchable()
                                ->preload()
                                ->label('Related Deal')
                                ->columnSpan(2),
                            Select::make('currency_code')
                                ->label('Currency')
                                ->options(config('company.currency_codes'))
                                ->default(config('company.default_currency', 'USD'))
                                ->native(false)
                                ->columnSpan(2),
                            DatePicker::make('valid_until')
                                ->label('Expires At')
                                ->native(false)
                                ->columnSpan(2),
                        ]),
                ])
                ->columns(12),
            Section::make('Address Information')
                ->schema([
                    Grid::make()
                        ->columns(6)
                        ->schema([
                            TextInput::make('billing_address.line1')
                                ->label('Billing Street')
                                ->maxLength(255)
                                ->columnSpan(3),
                            TextInput::make('billing_address.city')
                                ->label('Billing City')
                                ->maxLength(120)
                                ->columnSpan(2),
                            TextInput::make('billing_address.state')
                                ->label('Billing State')
                                ->maxLength(120)
                                ->columnSpan(1),
                            TextInput::make('billing_address.postal_code')
                                ->label('Billing Postal Code')
                                ->maxLength(20)
                                ->columnSpan(2),
                            TextInput::make('billing_address.country_code')
                                ->label('Billing Country')
                                ->default(config('address.default_country', 'US'))
                                ->maxLength(2)
                                ->columnSpan(2),
                        ]),
                    Grid::make()
                        ->columns(6)
                        ->schema([
                            TextInput::make('shipping_address.line1')
                                ->label('Shipping Street')
                                ->maxLength(255)
                                ->columnSpan(3),
                            TextInput::make('shipping_address.city')
                                ->label('Shipping City')
                                ->maxLength(120)
                                ->columnSpan(2),
                            TextInput::make('shipping_address.state')
                                ->label('Shipping State')
                                ->maxLength(120)
                                ->columnSpan(1),
                            TextInput::make('shipping_address.postal_code')
                                ->label('Shipping Postal Code')
                                ->maxLength(20)
                                ->columnSpan(2),
                            TextInput::make('shipping_address.country_code')
                                ->label('Shipping Country')
                                ->default(config('address.default_country', 'US'))
                                ->maxLength(2)
                                ->columnSpan(2),
                        ]),
                ])
                ->columns(12)
                ->collapsible(),
            Section::make('Quote Items')
                ->schema([
                    Repeater::make('lineItems')
                        ->relationship()
                        ->orderColumn('sort_order')
                        ->addActionLabel('Add item')
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            $teamId = auth()->user()?->currentTeam?->getKey();

                            return $teamId ? ['team_id' => $teamId] + $data : $data;
                        })
                        ->mutateRelationshipDataBeforeSaveUsing(function (array $data, Quote $record): array {
                            $teamId = $record->team_id ?? auth()->user()?->currentTeam?->getKey();

                            return $teamId ? ['team_id' => $teamId] + $data : $data;
                        })
                        ->table([
                            TableColumn::make('Product'),
                            TableColumn::make('Item')
                                ->markAsRequired(),
                            TableColumn::make('Qty')
                                ->markAsRequired()
                                ->alignment(Alignment::End),
                            TableColumn::make('Price')
                                ->markAsRequired()
                                ->alignment(Alignment::End),
                            TableColumn::make('Discount')
                                ->alignment(Alignment::End),
                            TableColumn::make('Tax %')
                                ->alignment(Alignment::End),
                        ])
                        ->compact()
                        ->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->options(fn () => Product::query()->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->columnSpan(3),
                            TextInput::make('name')
                                ->label('Item')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(5),
                            TextInput::make('quantity')
                                ->label('Qty')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(0.01)
                                ->step(0.01)
                                ->columnSpan(2),
                            TextInput::make('unit_price')
                                ->label('Unit price')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->minValue(0)
                                ->step(0.01)
                                ->columnSpan(2),
                            Select::make('discount_type')
                                ->label('Discount Type')
                                ->options(QuoteDiscountType::options())
                                ->default(QuoteDiscountType::PERCENT->value)
                                ->native(false)
                                ->columnSpan(2),
                            TextInput::make('discount_value')
                                ->label('Discount')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->step(0.01)
                                ->columnSpan(2),
                            TextInput::make('tax_rate')
                                ->label('Tax %')
                                ->numeric()
                                ->default(0)
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.01)
                                ->columnSpan(2),
                            Textarea::make('description')
                                ->label('Description')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])
                        ->columns(12)
                        ->columnSpanFull(),
                ])
                ->columns(12),
            Section::make('Decision')
                ->schema([
                    Textarea::make('decision_note')
                        ->label('Decision Notes')
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ])
                ->columns(12),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('Sales Owner')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('company.name')
                    ->label(__('app.labels.company'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('contact.name')
                    ->label('Person')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('lead.name')
                    ->label('Lead')
                    ->toggleable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => fn (QuoteStatus|string|null $state): bool => ($resolved = $state instanceof QuoteStatus ? $state : (is_string($state) ? QuoteStatus::tryFrom($state) : null)) === QuoteStatus::ACCEPTED,
                        'danger' => fn (QuoteStatus|string|null $state): bool => ($resolved = $state instanceof QuoteStatus ? $state : (is_string($state) ? QuoteStatus::tryFrom($state) : null)) === QuoteStatus::REJECTED,
                        'primary' => fn (QuoteStatus|string|null $state): bool => ($resolved = $state instanceof QuoteStatus ? $state : (is_string($state) ? QuoteStatus::tryFrom($state) : null)) === QuoteStatus::SENT,
                        'gray' => fn (QuoteStatus|string|null $state): bool => ($resolved = $state instanceof QuoteStatus ? $state : (is_string($state) ? QuoteStatus::tryFrom($state) : null)) === null
                            || $resolved === QuoteStatus::DRAFT,
                    ])
                    ->formatStateUsing(function (QuoteStatus|string|null $state): string {
                        $resolved = $state instanceof QuoteStatus ? $state : (is_string($state) ? QuoteStatus::tryFrom($state) : null);

                        return $resolved?->getLabel() ?? (is_string($state) ? $state : '');
                    })
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money(fn (Quote $record): string => $record->currency_code ?? 'USD')
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(QuoteStatus::options()),
                SelectFilter::make('owner_id')
                    ->relationship('owner', 'name')
                    ->label('Sales Owner'),
                SelectFilter::make('contact_id')
                    ->relationship('contact', 'name')
                    ->label('Person'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
            TasksRelationManager::class,
            NotesRelationManager::class,
            StatusHistoriesRelationManager::class,
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
                fn (Builder $query): Builder => $query->whereBelongsTo($tenant, 'team'),
            );
    }
}
