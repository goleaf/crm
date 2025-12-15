<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class AttributeAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attributeAssignments';

    protected static ?string $title = 'Attribute Assignments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_attribute_id')
                ->label('Attribute')
                ->relationship('attribute', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->reactive(),
            Select::make('product_attribute_value_id')
                ->label('Attribute Value')
                ->relationship('attributeValue', 'value')
                ->searchable()
                ->preload(),
            TextInput::make('custom_value')
                ->label('Custom Value')
                ->maxLength(255)
                ->helperText('Use this if the attribute value is not predefined'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('attribute.name')
                    ->label('Attribute')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attributeValue.value')
                    ->label('Value')
                    ->searchable(),
                TextColumn::make('custom_value')
                    ->label('Custom Value')
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
