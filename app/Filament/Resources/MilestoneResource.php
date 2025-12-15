<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\MilestonePriority;
use App\Enums\MilestoneStatus;
use App\Enums\MilestoneType;
use App\Filament\Resources\MilestoneResource\Pages\CreateMilestone;
use App\Filament\Resources\MilestoneResource\Pages\EditMilestone;
use App\Filament\Resources\MilestoneResource\Pages\ListMilestones;
use App\Filament\Resources\MilestoneResource\Pages\ViewMilestone;
use App\Filament\Resources\MilestoneResource\RelationManagers\ApprovalsRelationManager;
use App\Filament\Resources\MilestoneResource\RelationManagers\DeliverablesRelationManager;
use App\Filament\Resources\MilestoneResource\RelationManagers\DependenciesRelationManager;
use App\Filament\Resources\MilestoneResource\RelationManagers\TasksRelationManager;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;
use App\Services\Milestones\MilestoneService;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class MilestoneResource extends Resource
{
    protected static ?string $model = Milestone::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static ?int $navigationSort = 11;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.milestones');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.labels.milestone_details'))
                    ->schema([
                        Select::make('project_id')
                            ->label(__('app.labels.project'))
                            ->options(fn (): array => Project::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        TextInput::make('title')
                            ->label(__('app.labels.title'))
                            ->required()
                            ->maxLength(255),
                        Select::make('milestone_type')
                            ->label(__('app.labels.milestone_type'))
                            ->options(MilestoneType::class)
                            ->required(),
                        Select::make('priority_level')
                            ->label(__('app.labels.priority'))
                            ->options(MilestonePriority::class)
                            ->required()
                            ->default(MilestonePriority::MEDIUM),
                        Select::make('status')
                            ->label(__('app.labels.status'))
                            ->options(MilestoneStatus::class)
                            ->required()
                            ->default(MilestoneStatus::NOT_STARTED),
                        Select::make('owner_id')
                            ->label(__('app.labels.owner'))
                            ->options(function (Get $get): array {
                                $projectId = $get('project_id');

                                if (! is_int($projectId) && ! is_string($projectId)) {
                                    return [];
                                }

                                $project = Project::query()->find($projectId);

                                if (! $project instanceof Project) {
                                    return [];
                                }

                                return $project->teamMembers()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('target_date')
                            ->label(__('app.labels.target_date'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Get $get): void {
                                if ($state === null) {
                                    return;
                                }

                                $projectId = $get('project_id');

                                if (! is_int($projectId) && ! is_string($projectId)) {
                                    return;
                                }

                                $project = Project::query()->find($projectId);

                                if (! $project instanceof Project) {
                                    return;
                                }

                                $service = resolve(MilestoneService::class);
                                $result = $service->validateTargetDate($project, \Illuminate\Support\Facades\Date::parse($state));

                                if ($result['warnings'] === []) {
                                    return;
                                }

                                Notification::make()
                                    ->title(__('app.labels.warning'))
                                    ->body(implode("\n", $result['warnings']))
                                    ->warning()
                                    ->send();
                            }),
                        Toggle::make('is_critical')
                            ->label(__('app.labels.critical'))
                            ->default(false),
                        Toggle::make('requires_approval')
                            ->label(__('app.labels.requires_approval'))
                            ->default(false),
                        Select::make('stakeholder_ids')
                            ->label(__('app.labels.stakeholders'))
                            ->options(function (): array {
                                $teamId = Filament::getTenant()?->getKey();

                                return User::query()
                                    ->when($teamId, fn (Builder $query): Builder => $query->whereHas('teams', fn (Builder $builder): Builder => $builder->whereKey($teamId)))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Section::make(__('app.labels.description'))
                    ->schema([
                        RichEditor::make('description')
                            ->label(__('app.labels.description'))
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label(__('app.labels.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                Section::make(__('app.labels.goal_alignment'))
                    ->schema([
                        Select::make('goals')
                            ->label(__('app.labels.goals'))
                            ->relationship('goals', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ])
                    ->collapsed(),

                Section::make(__('app.labels.references'))
                    ->schema([
                        KeyValue::make('reference_links')
                            ->label(__('app.labels.reference_links'))
                            ->keyLabel(__('app.labels.label'))
                            ->valueLabel(__('app.labels.url'))
                            ->addButtonLabel(__('app.actions.add'))
                            ->columnSpanFull(),
                        \App\Filament\Support\UploadConstraints::apply(
                            SpatieMediaLibraryFileUpload::make('attachments')
                                ->label(__('app.labels.attachments'))
                                ->collection('attachments')
                                ->multiple()
                                ->appendFiles()
                                ->downloadable()
                                ->columnSpanFull(),
                            types: ['documents', 'images', 'archives'],
                        ),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_critical')
                    ->label(__('app.labels.critical'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('title')
                    ->label(__('app.labels.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('project.name')
                    ->label(__('app.labels.project'))
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label(__('app.labels.owner'))
                    ->toggleable(),
                TextColumn::make('milestone_type')
                    ->label(__('app.labels.milestone_type'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('priority_level')
                    ->label(__('app.labels.priority'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('target_date')
                    ->label(__('app.labels.target_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('completion_percentage')
                    ->label(__('app.labels.progress'))
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 0) . '%')
                    ->sortable(),
                IconColumn::make('is_at_risk')
                    ->label(__('app.labels.at_risk'))
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label(__('app.labels.project'))
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('owner_id')
                    ->label(__('app.labels.owner'))
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options(MilestoneStatus::class)
                    ->multiple(),
                SelectFilter::make('priority_level')
                    ->label(__('app.labels.priority'))
                    ->options(MilestonePriority::class)
                    ->multiple(),
                TrashedFilter::make(),
            ])
            ->defaultSort('target_date');
    }

    public static function getRelations(): array
    {
        return [
            DeliverablesRelationManager::class,
            TasksRelationManager::class,
            DependenciesRelationManager::class,
            ApprovalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMilestones::route('/'),
            'create' => CreateMilestone::route('/create'),
            'view' => ViewMilestone::route('/{record}'),
            'edit' => EditMilestone::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Milestone>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

