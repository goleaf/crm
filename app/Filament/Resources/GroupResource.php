<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\GroupResource\Pages;
use App\Filament\Resources\GroupResource\RelationManagers\PeopleRelationManager;
use App\Models\Group;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

final class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 150;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.settings');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.group');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.groups');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make(__('app.labels.group_details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('app.labels.name'))
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: function (Rule $rule): Rule {
                                    $teamId = auth()->user()?->currentTeam?->getKey() ?? auth()->user()?->current_team_id;

                                    return $teamId === null
                                        ? $rule
                                        : $rule->where(fn ($query) => $query->where('team_id', $teamId));
                                },
                            ),
                        Forms\Components\Textarea::make('description')
                            ->label(__('app.labels.description'))
                            ->rows(3)
                            ->maxLength(65535),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('app.labels.id'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('app.labels.description'))
                    ->limit(60)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('id')
                    ->form([
                        Forms\Components\TextInput::make('id')
                            ->label(__('app.labels.id'))
                            ->numeric(),
                    ])
                    ->query(fn ($query, array $data) => $query->when($data['id'] ?? null, fn ($q, $id) => $q->whereKey((int) $id))),
                Filter::make('name')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label(__('app.labels.name')),
                    ])
                    ->query(fn ($query, array $data) => $query->when($data['name'] ?? null, fn ($q, $name) => $q->where('name', 'like', '%' . $name . '%'))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PeopleRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
        ];
    }
}
