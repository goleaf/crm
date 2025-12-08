<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CreationSource;
use App\Filament\Exports\OpportunityExporter;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\OpportunityResource\Forms\OpportunityForm;
use App\Filament\Resources\OpportunityResource\Pages\ListOpportunities;
use App\Filament\Resources\OpportunityResource\Pages\ViewOpportunity;
use App\Filament\Resources\OpportunityResource\RelationManagers\NotesRelationManager;
use App\Filament\Resources\OpportunityResource\RelationManagers\TasksRelationManager;
use App\Filament\Support\Filters\DateScopeFilter;
use App\Models\Opportunity;
use App\Models\Tag;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Override;
use Relaticle\CustomFields\Facades\CustomFields;

final class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static ?int $navigationSort = 3;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return OpportunityForm::get($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('tags')
                    ->label(__('app.labels.tags'))
                    ->state(fn (Opportunity $record) => $record->tags)
                    ->formatStateUsing(fn (Tag $tag): string => $tag->name)
                    ->badge()
                    ->listWithLineBreaks()
                    ->color(fn (Tag $tag): array|string => $tag->color ? Color::hex($tag->color) : 'gray')
                    ->toggleable(),
                TextColumn::make('owner.name')
                    ->label(__('app.labels.owner'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                ...CustomFields::table()->forModel($table->getModel())->columns(),
                TextColumn::make('creator.name')
                    ->label(__('app.labels.created_by'))
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (Opportunity $record): string => $record->created_by)
                    ->color(fn (Opportunity $record): string => $record->isSystemCreated() ? 'secondary' : 'primary'),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                DateScopeFilter::make(),
                SelectFilter::make('creation_source')
                    ->label(__('app.labels.creation_source'))
                    ->options(CreationSource::class)
                    ->multiple(),
                SelectFilter::make('tags')
                    ->label(__('app.labels.tags'))
                    ->relationship(
                        'tags',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->when(
                            Auth::user()?->currentTeam,
                            fn (Builder $builder, $team): Builder => $builder->where('team_id', $team->getKey())
                        )
                    )
                    ->multiple()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    RestoreAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(OpportunityExporter::class),
                    RestoreBulkAction::make(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TasksRelationManager::class,
            NotesRelationManager::class,
            ActivitiesRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListOpportunities::route('/'),
            'view' => ViewOpportunity::route('/{record}'),
        ];
    }

    /**
     * @return Builder<Opportunity>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with('tags');
    }
}
