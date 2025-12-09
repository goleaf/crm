<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CreationSource;
use App\Enums\LeadAssignmentStrategy;
use App\Enums\LeadGrade;
use App\Enums\LeadNurtureStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Filament\Resources\LeadResource\Pages\ListLeadActivities;
use App\Filament\Exports\LeadExporter;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\LeadResource\Forms\LeadForm;
use App\Filament\Resources\LeadResource\Pages\CreateLead;
use App\Filament\Resources\LeadResource\Pages\ListLeads;
use App\Filament\Resources\LeadResource\Pages\ViewLead;
use App\Filament\Resources\LeadResource\RelationManagers\CalendarEventsRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\NotesRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\TasksRelationManager;
use App\Filament\Support\Filters\DateScopeFilter;
use App\Support\Helpers\NumberHelper;
use App\Models\Lead;
use App\Models\Tag;
use App\Models\Team;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
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
use Filament\Forms\Components\Select as FormSelect;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?int $navigationSort = 0;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return LeadForm::get($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.labels.lead'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_name')
                    ->label(__('app.labels.company'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (LeadStatus|string|null $state): string => $state instanceof LeadStatus ? $state->color() : 'secondary')
                    ->formatStateUsing(fn (LeadStatus|string|null $state): string => $state instanceof LeadStatus ? $state->getLabel() : (string) $state),
                TextColumn::make('source')
                    ->label(__('app.labels.source'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (LeadSource|string|null $state): string => $state instanceof LeadSource ? $state->getLabel() : (string) $state),
                TextColumn::make('lead_type')
                    ->label(__('app.labels.lead_type'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (LeadType|string|null $state): string => $state instanceof LeadType ? $state->getLabel() : (string) $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lead_value')
                    ->label(__('app.labels.lead_value'))
                    ->formatStateUsing(fn (mixed $state): string => NumberHelper::currency($state))
                    ->toggleable(),
                TextColumn::make('expected_close_date')
                    ->label(__('app.labels.expected_close_date'))
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('grade')
                    ->label(__('app.labels.grade'))
                    ->badge()
                    ->color(fn (LeadGrade|string|null $state): string => $state instanceof LeadGrade ? $state->color() : 'secondary')
                    ->formatStateUsing(fn (LeadGrade|string|null $state): string => $state instanceof LeadGrade ? $state->getLabel() : (string) $state),
                TextColumn::make('score')
                    ->label(__('app.labels.score'))
                    ->sortable(),
                TextColumn::make('assignedTo.name')
                    ->label(__('app.labels.assignee'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('territory')
                    ->label(__('app.labels.territory'))
                    ->toggleable(),
                TextColumn::make('tags')
                    ->label(__('app.labels.tags'))
                    ->state(fn (Lead $record) => $record->tags)
                    ->formatStateUsing(fn (Tag $tag): string => $tag->name)
                    ->badge()
                    ->listWithLineBreaks()
                    ->color(fn (Tag $tag): array|string => $tag->color ? Color::hex($tag->color) : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nurture_status')
                    ->label(__('app.labels.nurture_status'))
                    ->badge()
                    ->color(fn (LeadNurtureStatus|string|null $state): string => $state instanceof LeadNurtureStatus ? $state->color() : 'secondary')
                    ->formatStateUsing(fn (LeadNurtureStatus|string|null $state): string => $state instanceof LeadNurtureStatus ? $state->getLabel() : (string) $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creation_source')
                    ->label(__('app.labels.creation_source'))
                    ->badge()
                    ->color(fn (CreationSource|string|null $state): string => match ($state) {
                        CreationSource::IMPORT => 'success',
                        CreationSource::SYSTEM => 'warning',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (CreationSource|string|null $state): string => $state instanceof CreationSource ? $state->getLabel() : (string) $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                DateScopeFilter::make(),
                SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options(LeadStatus::options())
                    ->multiple(),
                SelectFilter::make('source')
                    ->label(__('app.labels.source'))
                    ->options(LeadSource::options())
                    ->multiple(),
                SelectFilter::make('lead_type')
                    ->label(__('app.labels.lead_type'))
                    ->options(LeadType::options())
                    ->multiple(),
                SelectFilter::make('grade')
                    ->label(__('app.labels.grade'))
                    ->options(LeadGrade::options())
                    ->multiple(),
                SelectFilter::make('assignment_strategy')
                    ->label(__('app.labels.assignment_strategy'))
                    ->options(LeadAssignmentStrategy::options())
                    ->multiple(),
                SelectFilter::make('nurture_status')
                    ->label(__('app.labels.nurture_status'))
                    ->options(LeadNurtureStatus::options())
                    ->multiple(),
                SelectFilter::make('creation_source')
                    ->label(__('app.labels.creation_source'))
                    ->options(CreationSource::class)
                    ->multiple(),
                SelectFilter::make('assigned_to_id')
                    ->label(__('app.labels.assignee'))
                    ->relationship('assignedTo', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                Filter::make('lead_value')
                    ->label(__('app.labels.lead_value'))
                    ->form([
                        Forms\Components\TextInput::make('min')
                            ->label('Min')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('max')
                            ->label('Max')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min'] ?? null,
                                fn (Builder $builder, mixed $min): Builder => $builder->where('lead_value', '>=', $min),
                            )
                            ->when(
                                $data['max'] ?? null,
                                fn (Builder $builder, mixed $max): Builder => $builder->where('lead_value', '<=', $max),
                            );
                    }),
                Filter::make('expected_close_date')
                    ->label(__('app.labels.expected_close_date'))
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $builder, string $date): Builder => $builder->whereDate('expected_close_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $builder, string $date): Builder => $builder->whereDate('expected_close_date', '<=', $date),
                            );
                    }),
                SelectFilter::make('tags')
                    ->label(__('app.labels.tags'))
                    ->relationship(
                        'tags',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->when(
                            Auth::user()?->currentTeam,
                            fn (Builder $builder, ?Team $team): Builder => $builder->where('team_id', $team?->getKey()),
                        ),
                    )
                    ->multiple()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('activities')
                        ->label(__('app.labels.activity'))
                        ->icon('heroicon-o-queue-list')
                        ->url(fn (Lead $record): string => self::getUrl('activities', [$record])),
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
                        ->exporter(LeadExporter::class),
                    BulkAction::make('bulk_status')
                        ->label(__('app.actions.update_status'))
                        ->form([
                            FormSelect::make('status')
                                ->label(__('app.labels.status'))
                                ->options(LeadStatus::options())
                                ->required(),
                        ])
                        ->action(fn (array $data, Collection $records) => $records->each->update(['status' => $data['status']])),
                    BulkAction::make('bulk_assignment')
                        ->label(__('app.actions.assign_to'))
                        ->form([
                            FormSelect::make('assigned_to_id')
                                ->label(__('app.labels.assignee'))
                                ->relationship('assignedTo', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(fn (array $data, Collection $records) => $records->each->update([
                            'assigned_to_id' => $data['assigned_to_id'],
                            'assignment_strategy' => LeadAssignmentStrategy::MANUAL,
                        ])),
                    BulkAction::make('bulk_nurture')
                        ->label(__('app.actions.set_nurture_status'))
                        ->form([
                            FormSelect::make('nurture_status')
                                ->label(__('app.labels.nurture_status'))
                                ->options(LeadNurtureStatus::options())
                                ->required(),
                        ])
                        ->action(fn (array $data, Collection $records) => $records->each->update(['nurture_status' => $data['nurture_status']])),
                    RestoreBulkAction::make(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CalendarEventsRelationManager::class,
            TasksRelationManager::class,
            NotesRelationManager::class,
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'view' => ViewLead::route('/{record}'),
            'activities' => ListLeadActivities::route('/{record}/activities'),
        ];
    }

    /**
     * @return Builder<Lead>
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
