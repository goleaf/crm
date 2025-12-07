<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CaseChannel;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Enums\CreationSource;
use App\Filament\Exports\SupportCaseExporter;
use App\Filament\Resources\SupportCaseResource\Forms\SupportCaseForm;
use App\Filament\Resources\SupportCaseResource\Pages\CreateSupportCase;
use App\Filament\Resources\SupportCaseResource\Pages\EditSupportCase;
use App\Filament\Resources\SupportCaseResource\Pages\ListSupportCases;
use App\Filament\Resources\SupportCaseResource\Pages\ViewSupportCase;
use App\Filament\Resources\SupportCaseResource\RelationManagers\NotesRelationManager;
use App\Filament\Resources\SupportCaseResource\RelationManagers\TasksRelationManager;
use App\Models\SupportCase;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Override;
use Relaticle\CustomFields\Facades\CustomFields;

final class SupportCaseResource extends Resource
{
    protected static ?string $model = SupportCase::class;

    protected static ?string $recordTitleAttribute = 'subject';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?int $navigationSort = 4;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.labels.cases');
    }

    public static function form(Schema $schema): Schema
    {
        return SupportCaseForm::get($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('case_number')
                    ->label(__('app.labels.case_number'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('subject')
                    ->label(__('app.labels.title'))
                    ->wrap()
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (CaseStatus|string|null $state): string => $state instanceof CaseStatus ? $state->getColor() : (CaseStatus::tryFrom((string) $state)?->getColor() ?? 'gray'))
                    ->formatStateUsing(fn (CaseStatus|string|null $state): string => $state instanceof CaseStatus ? $state->getLabel() : (CaseStatus::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                TextColumn::make('priority')
                    ->label(__('app.labels.priority'))
                    ->badge()
                    ->color(fn (CasePriority|string|null $state): string => $state instanceof CasePriority ? $state->getColor() : (CasePriority::tryFrom((string) $state)?->getColor() ?? 'gray'))
                    ->formatStateUsing(fn (CasePriority|string|null $state): string => $state instanceof CasePriority ? $state->getLabel() : (CasePriority::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                TextColumn::make('type')
                    ->label(__('app.labels.type'))
                    ->badge()
                    ->formatStateUsing(fn (CaseType|string|null $state): string => $state instanceof CaseType ? $state->getLabel() : (CaseType::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                TextColumn::make('channel')
                    ->label(__('app.labels.channel'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (CaseChannel|string|null $state): string => $state instanceof CaseChannel ? $state->getLabel() : (CaseChannel::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                TextColumn::make('queue')
                    ->label(__('app.labels.queue'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('company.name')
                    ->label(__('app.labels.company'))
                    ->toggleable(),
                TextColumn::make('contact.name')
                    ->label(__('app.labels.contact_person'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('assignee.name')
                    ->label(__('app.labels.assignee'))
                    ->toggleable(),
                TextColumn::make('assignedTeam.name')
                    ->label(__('app.labels.assigned_team'))
                    ->toggleable(isToggledHiddenByDefault: true),
                ...CustomFields::table()->forModel($table->getModel())->columns(),
                TextColumn::make('sla_due_at')
                    ->label(__('app.labels.sla_due_at'))
                    ->since()
                    ->color(fn (SupportCase $record): ?string => $record->sla_due_at !== null && $record->resolved_at === null && $record->sla_due_at->isPast() ? 'danger' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('first_response_at')
                    ->label(__('app.labels.first_response_at'))
                    ->state(fn (SupportCase $record): string => $record->first_response_at?->diffForHumans($record->created_at, absolute: true, short: true, parts: 2) ?? '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('resolved_at')
                    ->label(__('app.labels.resolved_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('app.labels.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('app.labels.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options(CaseStatus::class)
                    ->multiple(),
                SelectFilter::make('priority')
                    ->label(__('app.labels.priority'))
                    ->options(CasePriority::class)
                    ->multiple(),
                SelectFilter::make('type')
                    ->label(__('app.labels.type'))
                    ->options(CaseType::class)
                    ->multiple(),
                SelectFilter::make('channel')
                    ->label(__('app.labels.channel'))
                    ->options(CaseChannel::class)
                    ->multiple(),
                SelectFilter::make('queue')
                    ->label(__('app.labels.queue'))
                    ->options([
                        'general' => 'General',
                        'billing' => 'Billing',
                        'technical' => 'Technical Support',
                        'product' => 'Product',
                    ])
                    ->multiple(),
                SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('app.labels.company'))
                    ->searchable(),
                SelectFilter::make('assigned_to_id')
                    ->relationship('assignee', 'name')
                    ->label(__('app.labels.assignee'))
                    ->searchable(),
                SelectFilter::make('assigned_team_id')
                    ->relationship('assignedTeam', 'name')
                    ->label(__('app.labels.assigned_team'))
                    ->searchable(),
                SelectFilter::make('creation_source')
                    ->label(__('app.labels.creation_source'))
                    ->options(CreationSource::class)
                    ->multiple(),
                Filter::make('overdue_sla')
                    ->label('SLA Breached')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('sla_due_at')
                        ->whereNull('resolved_at')
                        ->where('sla_due_at', '<', now())),
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
                    ExportBulkAction::make()->exporter(SupportCaseExporter::class),
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
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListSupportCases::route('/'),
            'create' => CreateSupportCase::route('/create'),
            'edit' => EditSupportCase::route('/{record}/edit'),
            'view' => ViewSupportCase::route('/{record}'),
        ];
    }

    /**
     * @return Builder<SupportCase>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
