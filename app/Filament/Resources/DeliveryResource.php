<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\DeliveryStatus;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\DeliveryResource\Pages\CreateDelivery;
use App\Filament\Resources\DeliveryResource\Pages\EditDelivery;
use App\Filament\Resources\DeliveryResource\Pages\ListDeliveries;
use App\Filament\Resources\DeliveryResource\Pages\ViewDelivery;
use App\Models\Delivery;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 9;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('order_id')
                ->relationship('order', 'id')
                ->label('Order')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('status')
                ->options(DeliveryStatus::options())
                ->default(DeliveryStatus::PENDING)
                ->required()
                ->native(false),
            TextInput::make('tracking_number')
                ->maxLength(255),
            DateTimePicker::make('shipped_at')
                ->label('Shipped At'),
            DateTimePicker::make('delivered_at')
                ->label('Delivered At'),
            Textarea::make('notes')
                ->maxLength(1000)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Delivery #')->sortable(),
                TextColumn::make('order_id')->label('Order #')->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'gray' => DeliveryStatus::PENDING->value,
                        'primary' => DeliveryStatus::SHIPPED->value,
                        'success' => DeliveryStatus::DELIVERED->value,
                    ])
                    ->formatStateUsing(fn (DeliveryStatus|string|null $state): string => $state instanceof DeliveryStatus ? $state->getLabel() : (string) $state)
                    ->sortable(),
                TextColumn::make('tracking_number')->toggleable(),
                TextColumn::make('shipped_at')->dateTime()->toggleable(),
                TextColumn::make('delivered_at')->dateTime()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveries::route('/'),
            'create' => CreateDelivery::route('/create'),
            'view' => ViewDelivery::route('/{record}'),
            'edit' => EditDelivery::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Delivery>
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
