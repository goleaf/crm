<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\OrderResource\RelationManagers\DeliveriesRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\NotesRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\TasksRelationManager;
use App\Models\Order;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('company_id')
                ->relationship('company', 'name')
                ->label(__('app.labels.company'))
                ->searchable()
                ->preload()
                ->required(),
            Select::make('contact_id')
                ->relationship('contact', 'name')
                ->label('Contact')
                ->searchable()
                ->preload(),
            Select::make('opportunity_id')
                ->relationship('opportunity', 'name')
                ->label('Deal')
                ->searchable()
                ->preload(),
            Select::make('quote_id')
                ->relationship('quote', 'title')
                ->label('Quote')
                ->searchable()
                ->preload(),
            Select::make('status')
                ->options(OrderStatus::options())
                ->default(OrderStatus::DRAFT)
                ->required()
                ->native(false),
            TextInput::make('currency_code')
                ->label('Currency')
                ->maxLength(3)
                ->default('USD'),
            DatePicker::make('expected_delivery_date')
                ->label('Expected Delivery')
                ->native(false),
            Repeater::make('line_items')
                ->label('Line Items')
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('quantity')->numeric()->default(1)->minValue(0),
                    TextInput::make('unit_price')->numeric()->default(0)->minValue(0),
                    TextInput::make('tax_rate')->numeric()->default(0)->minValue(0)->suffix('%'),
                ])
                ->columns(4)
                ->default([])
                ->columnSpanFull(),
            Textarea::make('notes')
                ->label('Internal Notes')
                ->maxLength(1000)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Order #')
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label(__('app.labels.company'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('contact.name')
                    ->label('Contact')
                    ->toggleable(),
                BadgeColumn::make('status')
                    ->colors([
                        'gray' => OrderStatus::DRAFT->value,
                        'warning' => OrderStatus::PENDING->value,
                        'primary' => OrderStatus::CONFIRMED->value,
                        'success' => OrderStatus::FULFILLED->value,
                        'danger' => OrderStatus::CANCELLED->value,
                    ])
                    ->formatStateUsing(fn (OrderStatus|string|null $state): string => $state instanceof OrderStatus ? $state->getLabel() : (string) $state)
                    ->sortable(),
                TextColumn::make('total')
                    ->money(fn (Order $record): string => $record->currency_code ?? 'USD')
                    ->sortable(),
                TextColumn::make('expected_delivery_date')
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
            InvoicesRelationManager::class,
            DeliveriesRelationManager::class,
            TasksRelationManager::class,
            NotesRelationManager::class,
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Order>
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
