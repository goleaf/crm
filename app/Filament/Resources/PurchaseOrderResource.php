<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\PurchaseOrderStatus;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\PurchaseOrderResource\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseOrderResource\Pages\EditPurchaseOrder;
use App\Filament\Resources\PurchaseOrderResource\Pages\ListPurchaseOrders;
use App\Filament\Resources\PurchaseOrderResource\Pages\ViewPurchaseOrder;
use App\Models\PurchaseOrder;
use Filament\Facades\Filament;
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

final class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('supplier_name')
                ->required()
                ->maxLength(255),
            Select::make('status')
                ->options(PurchaseOrderStatus::options())
                ->default(PurchaseOrderStatus::DRAFT)
                ->required()
                ->native(false),
            TextInput::make('currency_code')
                ->label('Currency')
                ->maxLength(3)
                ->default('USD'),
            Repeater::make('line_items')
                ->label('Line Items')
                ->table([
                    TableColumn::make('Item')
                        ->markAsRequired(),
                    TableColumn::make('Qty')
                        ->markAsRequired()
                        ->alignment(Alignment::End),
                    TableColumn::make('Unit price')
                        ->markAsRequired()
                        ->alignment(Alignment::End),
                ])
                ->compact()
                ->schema([
                    TextInput::make('name')
                        ->label('Item')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('quantity')
                        ->label('Qty')
                        ->numeric()
                        ->default(1)
                        ->minValue(0),
                    TextInput::make('unit_price')
                        ->label('Unit price')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                ])
                ->default([])
                ->columnSpanFull(),
            Textarea::make('notes')
                ->maxLength(1000)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('PO #')->sortable(),
                TextColumn::make('supplier_name')->searchable()->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'gray' => PurchaseOrderStatus::DRAFT->value,
                        'primary' => PurchaseOrderStatus::SENT->value,
                        'success' => PurchaseOrderStatus::RECEIVED->value,
                        'danger' => PurchaseOrderStatus::CANCELLED->value,
                    ])
                    ->formatStateUsing(fn (PurchaseOrderStatus|string|null $state): string => $state instanceof PurchaseOrderStatus ? $state->getLabel() : (string) $state)
                    ->sortable(),
                TextColumn::make('total')
                    ->money(fn (PurchaseOrder $record): string => $record->currency_code ?? 'USD')
                    ->sortable(),
                TextColumn::make('created_at')->date()->sortable(),
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
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'view' => ViewPurchaseOrder::route('/{record}'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<PurchaseOrder>
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
