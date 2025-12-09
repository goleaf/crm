<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ProjectStatus;
use App\Filament\Resources\ProjectResource\Pages\CreateProject;
use App\Filament\Resources\ProjectResource\Pages\EditProject;
use App\Filament\Resources\ProjectResource\Pages\ListProjects;
use App\Filament\Resources\ProjectResource\Pages\ViewProject;
use App\Filament\Resources\ProjectResource\Pages\ViewProjectSchedule;
use App\Models\Project;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 10;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.labels.project_details'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label(__('app.labels.name'))
                                ->required()
                                ->maxLength(255),
                            Select::make('status')
                                ->label(__('app.labels.status'))
                                ->options(ProjectStatus::class)
                                ->required()
                                ->default(ProjectStatus::PLANNING),
                        ]),
                        RichEditor::make('description')
                            ->label(__('app.labels.description'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('app.labels.schedule'))
                    ->schema([
                        Grid::make(2)->schema([
                            DatePicker::make('start_date')
                                ->label(__('app.labels.start_date')),
                            DatePicker::make('end_date')
                                ->label(__('app.labels.end_date')),
                        ]),
                    ]),

                Section::make(__('app.labels.budget'))
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('budget')
                                ->label(__('app.labels.budget'))
                                ->numeric()
                                ->prefix('$'),
                            TextInput::make('currency')
                                ->label(__('app.labels.currency'))
                                ->default('USD')
                                ->maxLength(3),
                            Placeholder::make('actual_cost')
                                ->label(__('app.labels.actual_cost'))
                                ->content(fn (?Project $record): string => $record instanceof \App\Models\Project ? '$' . number_format($record->actual_cost, 2) : '$0.00'),
                        ]),
                    ]),

                Section::make(__('app.labels.template'))
                    ->schema([
                        Toggle::make('is_template')
                            ->label(__('app.labels.is_template'))
                            ->helperText(__('app.messages.template_help')),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label(__('app.labels.start_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('end_date')
                    ->label(__('app.labels.end_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('percent_complete')
                    ->label(__('app.labels.progress'))
                    ->formatStateUsing(fn (float $state): string => number_format($state, 0) . '%')
                    ->sortable(),
                TextColumn::make('budget')
                    ->label(__('app.labels.budget'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('actual_cost')
                    ->label(__('app.labels.actual_cost'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options(ProjectStatus::class)
                    ->multiple(),
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'view' => ViewProject::route('/{record}'),
            'edit' => EditProject::route('/{record}/edit'),
            'schedule' => ViewProjectSchedule::route('/{record}/schedule'),
        ];
    }

    /**
     * @return Builder<Project>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
