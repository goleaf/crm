<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class RelationshipsRelationManager extends RelationManager
{
    protected static string $relationship = 'relationships';

    protected static ?string $title = 'Product Relationships';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('related_product_id')
                ->label('Related Product')
                ->required()
                ->options(fn (): array => Product::query()
                    ->where('team_id')
                    ->whereKeyNot($this->ownerRecord->getKey())
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable(),
            Select::make('relationship_type')
                ->required()
                ->options([
                    'bundle' => 'Bundle Component',
                    'cross_sell' => 'Cross-sell',
                    'upsell' => 'Upsell',
                    'dependency' => 'Dependency',
                ]),
            TextInput::make('quantity')
                ->numeric()
                ->default(1)
                ->minValue(1),
            TextInput::make('price_override')
                ->label('Bundle Price Override')
                ->numeric()
                ->minValue(0)
                ->prefix('$'),
            TextInput::make('priority')
                ->numeric()
                ->default(0),
            \Filament\Forms\Components\Toggle::make('is_required')
                ->label('Required (dependency)')
                ->default(false),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('relatedProduct.name')
                    ->label('Related Product')
                    ->searchable(),
                BadgeColumn::make('relationship_type')
                    ->label('Type')
                    ->colors([
                        'primary',
                        'warning' => 'dependency',
                        'success' => 'upsell',
                    ]),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable(),
                TextColumn::make('price_override')
                    ->label('Override')
                    ->money(fn ($record) => $record->relatedProduct?->currency_code ?? 'USD'),
                TextColumn::make('priority')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => [
                        ...$data,
                        'team_id' => $this->ownerRecord->team_id,
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
