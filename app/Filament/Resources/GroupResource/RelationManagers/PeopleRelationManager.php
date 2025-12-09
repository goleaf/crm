<?php

declare(strict_types=1);

namespace App\Filament\Resources\GroupResource\RelationManagers;

use App\Models\People;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class PeopleRelationManager extends RelationManager
{
    protected static string $relationship = 'people';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('people_id')
                    ->label(__('app.labels.person'))
                    ->options(People::query()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('primary_email')
                    ->label(__('app.labels.email'))
                    ->icon('heroicon-o-envelope')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('app.labels.company'))
                    ->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
