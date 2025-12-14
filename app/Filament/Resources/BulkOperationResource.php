<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\BulkOperationStatus;
use App\Enums\BulkOperationType;
use App\Filament\Resources\BulkOperationResource\Pages;
use App\Models\BulkOperation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class BulkOperationResource extends Resource
{
    protected static ?string $model = BulkOperation::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-squares-plus';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.data_quality');
    }

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.bulk_operations');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.bulk_operation');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.bulk_operations');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('app.labels.operation_details'))
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label(__('app.labels.operation_type'))
                            ->options(BulkOperationType::class)
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('model_type')
                            ->label(__('app.labels.model_type'))
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label(__('app.labels.status'))
                            ->options(BulkOperationStatus::class)
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make(__('app.labels.statistics'))
                    ->schema([
                        Forms\Components\TextInput::make('total_records')
                            ->label(__('app.labels.total_records'))
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('processed_records')
                            ->label(__('app.labels.processed_records'))
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('failed_records')
                            ->label(__('app.labels.failed_records'))
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('batch_size')
                            ->label(__('app.labels.batch_size'))
                            ->numeric()
                            ->disabled(),
                    ])->columns(4),

                Forms\Components\Section::make(__('app.labels.operation_data'))
                    ->schema([
                        Forms\Components\KeyValue::make('operation_data')
                            ->label(__('app.labels.operation_data'))
                            ->disabled(),
                    ]),

                Forms\Components\Section::make(__('app.labels.errors'))
                    ->schema([
                        Forms\Components\Textarea::make('errors')
                            ->label(__('app.labels.errors'))
                            ->formatStateUsing(fn (?array $state): string => $state ? implode("\n", $state) : '',
                            )
                            ->disabled()
                            ->rows(5),
                    ])
                    ->visible(fn (?BulkOperation $record): bool => $record && ! empty($record->errors),
                    ),

                Forms\Components\Section::make(__('app.labels.timing'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label(__('app.labels.started_at'))
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label(__('app.labels.completed_at'))
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('app.labels.id'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('app.labels.type'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('model_type')
                    ->label(__('app.labels.model'))
                    ->formatStateUsing(fn (string $state): string => class_basename($state),
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('progress')
                    ->label(__('app.labels.progress'))
                    ->getStateUsing(fn (BulkOperation $record): string => $record->progress_percentage . '%',
                    )
                    ->color(fn (BulkOperation $record): string => match (true) {
                        $record->progress_percentage === 100.0 => 'success',
                        $record->progress_percentage >= 50.0 => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_records')
                    ->label(__('app.labels.total'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('processed_records')
                    ->label(__('app.labels.processed'))
                    ->numeric()
                    ->color('success'),

                Tables\Columns\TextColumn::make('failed_records')
                    ->label(__('app.labels.failed'))
                    ->numeric()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label(__('app.labels.created_by'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label(__('app.labels.duration'))
                    ->getStateUsing(fn (BulkOperation $record): ?string => $record->duration ? gmdate('H:i:s', $record->duration) : null,
                    )
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('app.labels.type'))
                    ->options(BulkOperationType::class),

                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options(BulkOperationStatus::class),

                Tables\Filters\SelectFilter::make('model_type')
                    ->label(__('app.labels.model'))
                    ->options([
                        \App\Models\Company::class => 'Company',
                        \App\Models\People::class => 'People',
                        \App\Models\Task::class => 'Task',
                        \App\Models\Note::class => 'Note',
                        \App\Models\Opportunity::class => 'Opportunity',
                        \App\Models\SupportCase::class => 'Support Case',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('cancel')
                    ->label(__('app.actions.cancel'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (BulkOperation $record): bool => $record->status === BulkOperationStatus::PROCESSING,
                    )
                    ->action(function (BulkOperation $record): void {
                        $record->update([
                            'status' => BulkOperationStatus::CANCELLED,
                            'completed_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title(__('app.notifications.bulk_operation_cancelled'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()->can('delete', BulkOperation::class)),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['createdBy', 'team']);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBulkOperations::route('/'),
            'view' => Pages\ViewBulkOperation::route('/{record}'),
        ];
    }
}
