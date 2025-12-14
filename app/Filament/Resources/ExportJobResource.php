<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ExportJobResource\Pages\CreateExportJob;
use App\Filament\Resources\ExportJobResource\Pages\EditExportJob;
use App\Filament\Resources\ExportJobResource\Pages\ListExportJobs;
use App\Filament\Resources\ExportJobResource\Pages\ViewExportJob;
use App\Models\ExportJob;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ExportJobResource extends Resource
{
    protected static ?string $model = ExportJob::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.data_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.export_jobs');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('app.labels.name'))
                    ->required()
                    ->maxLength(255),

                Select::make('model_type')
                    ->label(__('app.labels.model_type'))
                    ->options([
                        'Company' => __('app.models.company'),
                        'People' => __('app.models.people'),
                        'Opportunity' => __('app.models.opportunity'),
                        'Task' => __('app.models.task'),
                        'Note' => __('app.models.note'),
                        'Lead' => __('app.models.lead'),
                        'SupportCase' => __('app.models.support_case'),
                    ])
                    ->required(),

                Select::make('format')
                    ->label(__('app.labels.format'))
                    ->options([
                        'csv' => 'CSV',
                        'xlsx' => 'Excel (XLSX)',
                    ])
                    ->default('csv')
                    ->required(),

                Select::make('scope')
                    ->label(__('app.labels.scope'))
                    ->options([
                        'all' => __('app.labels.all_records'),
                        'filtered' => __('app.labels.filtered_records'),
                        'selected' => __('app.labels.selected_records'),
                    ])
                    ->default('all')
                    ->required(),

                KeyValue::make('selected_fields')
                    ->label(__('app.labels.selected_fields'))
                    ->helperText(__('app.helpers.export_selected_fields')),

                KeyValue::make('filters')
                    ->label(__('app.labels.filters'))
                    ->helperText(__('app.helpers.export_filters')),

                KeyValue::make('template_config')
                    ->label(__('app.labels.template_config'))
                    ->helperText(__('app.helpers.export_template_config')),

                DateTimePicker::make('expires_at')
                    ->label(__('app.labels.expires_at'))
                    ->default(now()->addDays(7)),

                Textarea::make('error_message')
                    ->label(__('app.labels.error_message'))
                    ->rows(3)
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model_type')
                    ->label(__('app.labels.model_type'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('format')
                    ->label(__('app.labels.format'))
                    ->badge()
                    ->color('info'),

                BadgeColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ])
                    ->sortable(),

                TextColumn::make('progress')
                    ->label(__('app.labels.progress'))
                    ->getStateUsing(fn (ExportJob $record): string => $record->total_records > 0
                            ? $record->getProgressPercentage() . '%'
                            : '0%',
                    ),

                TextColumn::make('total_records')
                    ->label(__('app.labels.total_records'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('successful_records')
                    ->label(__('app.labels.successful_records'))
                    ->numeric()
                    ->color('success'),

                TextColumn::make('failed_records')
                    ->label(__('app.labels.failed_records'))
                    ->numeric()
                    ->color('danger'),

                TextColumn::make('file_size')
                    ->label(__('app.labels.file_size'))
                    ->getStateUsing(fn (ExportJob $record): ?string => $record->getFileSizeFormatted()),

                TextColumn::make('user.name')
                    ->label(__('app.labels.created_by'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('completed_at')
                    ->label(__('app.labels.completed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('expires_at')
                    ->label(__('app.labels.expires_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options([
                        'pending' => __('app.labels.pending'),
                        'processing' => __('app.labels.processing'),
                        'completed' => __('app.labels.completed'),
                        'failed' => __('app.labels.failed'),
                    ]),

                SelectFilter::make('model_type')
                    ->label(__('app.labels.model_type'))
                    ->options([
                        'Company' => __('app.models.company'),
                        'People' => __('app.models.people'),
                        'Opportunity' => __('app.models.opportunity'),
                        'Task' => __('app.models.task'),
                        'Note' => __('app.models.note'),
                    ]),

                SelectFilter::make('format')
                    ->label(__('app.labels.format'))
                    ->options([
                        'csv' => 'CSV',
                        'xlsx' => 'Excel (XLSX)',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (ExportJob $record): bool => $record->isPending()),
                Action::make('download')
                    ->label(__('app.actions.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (ExportJob $record): bool => $record->isCompleted() && $record->file_path && ! $record->isExpired())
                    ->url(fn (ExportJob $record): ?string => $record->getFileUrl())
                    ->openUrlInNewTab(),
                Action::make('retry')
                    ->label(__('app.actions.retry'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (ExportJob $record): bool => $record->isFailed())
                    ->action(function (ExportJob $record): void {
                        $record->update([
                            'status' => 'pending',
                            'error_message' => null,
                            'errors' => null,
                            'started_at' => null,
                            'completed_at' => null,
                            'processed_records' => 0,
                            'successful_records' => 0,
                            'failed_records' => 0,
                        ]);
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExportJobs::route('/'),
            'create' => CreateExportJob::route('/create'),
            'view' => ViewExportJob::route('/{record}'),
            'edit' => EditExportJob::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<ExportJob>
     */
    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->when(
                $tenant,
                fn (Builder $query): Builder => $query->whereBelongsTo($tenant, 'team'),
            );
    }
}
