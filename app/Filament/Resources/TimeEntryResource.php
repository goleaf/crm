<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\TimeEntryApprovalStatus;
use App\Filament\Resources\TimeEntryResource\Pages\ManageTimeEntries;
use App\Filament\Support\Filters\DateScopeFilter;
use App\Models\Employee;
use App\Models\TimeEntry;
use App\Services\TimeManagement\TimeEntryService;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'description';

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.time_entries');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.time_entry');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.time_entries');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.labels.time_entry'))
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('employee_id')
                                ->label(__('app.labels.employee'))
                                ->relationship('employee', 'first_name')
                                ->searchable()
                                ->preload()
                                ->getOptionLabelFromRecordUsing(fn (Employee $record): string => $record->full_name)
                                ->required(),
                            DatePicker::make('date')
                                ->label(__('app.labels.date'))
                                ->required(),
                        ]),
                        Grid::make(3)->schema([
                            DateTimePicker::make('start_time')
                                ->label(__('app.labels.start_time'))
                                ->native(false)
                                ->seconds(false)
                                ->required(fn (Get $get): bool => filled($get('end_time'))),
                            DateTimePicker::make('end_time')
                                ->label(__('app.labels.end_time'))
                                ->native(false)
                                ->seconds(false)
                                ->required(fn (Get $get): bool => filled($get('start_time'))),
                            TextInput::make('duration_minutes')
                                ->label(__('app.labels.duration_minutes'))
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(1440)
                                ->required(fn (Get $get): bool => blank($get('start_time')) && blank($get('end_time'))),
                        ]),
                        Textarea::make('description')
                            ->label(__('app.labels.description'))
                            ->required()
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label(__('app.labels.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('app.labels.associations'))
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('project_id')
                                ->label(__('app.labels.project'))
                                ->relationship('project', 'name')
                                ->searchable()
                                ->preload(),
                            Select::make('company_id')
                                ->label(__('app.labels.company'))
                                ->relationship('company', 'name')
                                ->searchable()
                                ->preload(),
                            Select::make('task_id')
                                ->label(__('app.labels.task'))
                                ->relationship('task', 'title')
                                ->searchable(),
                            Select::make('time_category_id')
                                ->label(__('app.labels.time_category'))
                                ->relationship('timeCategory', 'name')
                                ->searchable()
                                ->preload(),
                        ]),
                    ])
                    ->collapsible(),

                Section::make(__('app.labels.billing'))
                    ->schema([
                        Toggle::make('is_billable')
                            ->label(__('app.labels.billable'))
                            ->inline(false)
                            ->live(),
                        TextInput::make('billing_rate')
                            ->label(__('app.labels.billing_rate'))
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('$')
                            ->visible(fn (Get $get): bool => (bool) $get('is_billable'))
                            ->helperText(__('app.helpers.billing_rate_optional')),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label(__('app.labels.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('employee.full_name')
                    ->label(__('app.labels.employee'))
                    ->getStateUsing(fn (TimeEntry $record): string => $record->employee?->full_name ?? '')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('project.name')
                    ->label(__('app.labels.project'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('company.name')
                    ->label(__('app.labels.company'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('task.title')
                    ->label(__('app.labels.task'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('timeCategory.name')
                    ->label(__('app.labels.time_category'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('duration_minutes')
                    ->label(__('app.labels.duration'))
                    ->formatStateUsing(fn (int $state): string => number_format($state / 60, 2) . ' h')
                    ->sortable(),
                TextColumn::make('is_billable')
                    ->label(__('app.labels.billable'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('app.labels.billable') : __('app.labels.non_billable'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('approval_status')
                    ->label(__('app.labels.approval_status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('billing_amount')
                    ->label(__('app.labels.billing_amount'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                DateScopeFilter::make('date_range', 'date'),
                SelectFilter::make('approval_status')
                    ->label(__('app.labels.approval_status'))
                    ->options(TimeEntryApprovalStatus::class)
                    ->multiple(),
                SelectFilter::make('employee_id')
                    ->label(__('app.labels.employee'))
                    ->relationship('employee', 'first_name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('project_id')
                    ->label(__('app.labels.project'))
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('company_id')
                    ->label(__('app.labels.company'))
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('time_category_id')
                    ->label(__('app.labels.time_category'))
                    ->relationship('timeCategory', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->databaseTransaction()
                        ->using(function (TimeEntry $record, array $data): TimeEntry {
                            $actor = auth()->user();

                            return resolve(TimeEntryService::class)->updateTimeEntry($record, $data, $actor);
                        }),
                    RestoreAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTimeEntries::route('/'),
        ];
    }

    /**
     * @return Builder<TimeEntry>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

