<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ImportJobResource\Pages;
use App\Models\ImportJob;
use App\Services\Import\ImportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ImportJobResource extends Resource
{
    protected static ?string $model = ImportJob::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.data_management');
    }

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.imports');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.import');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.imports');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('app.labels.import_details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('app.labels.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('model_type')
                            ->label(__('app.labels.model_type'))
                            ->options([
                                'Company' => __('app.labels.company'),
                                'People' => __('app.labels.people'),
                                'Contact' => __('app.labels.contact'),
                                'Lead' => __('app.labels.lead'),
                                'Opportunity' => __('app.labels.opportunity'),
                                'Task' => __('app.labels.task'),
                                'Note' => __('app.labels.note'),
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\FileUpload::make('file_path')
                            ->label(__('app.labels.file'))
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/vcard'])
                            ->maxSize(10240) // 10MB
                            ->directory('imports')
                            ->visibility('private')
                            ->required()
                            ->afterStateUpdated(function ($state, $set, $get): void {
                                if ($state) {
                                    $set('original_filename', $state->getClientOriginalName());
                                    $set('file_size', $state->getSize());
                                }
                            }),

                        Forms\Components\Select::make('status')
                            ->label(__('app.labels.status'))
                            ->options([
                                'pending' => __('app.labels.pending'),
                                'processing' => __('app.labels.processing'),
                                'completed' => __('app.labels.completed'),
                                'failed' => __('app.labels.failed'),
                            ])
                            ->default('pending')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('app.labels.statistics'))
                    ->schema([
                        Forms\Components\TextInput::make('total_rows')
                            ->label(__('app.labels.total_rows'))
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('processed_rows')
                            ->label(__('app.labels.processed_rows'))
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('successful_rows')
                            ->label(__('app.labels.successful_rows'))
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('failed_rows')
                            ->label(__('app.labels.failed_rows'))
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('duplicate_rows')
                            ->label(__('app.labels.duplicate_rows'))
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(3)
                    ->visible(fn ($record): bool => $record && $record->total_rows > 0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('model_type')
                    ->label(__('app.labels.model_type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Company' => 'info',
                        'People' => 'success',
                        'Lead' => 'warning',
                        'Opportunity' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('app.labels.file_type'))
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_rows')
                    ->label(__('app.labels.total_rows'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('successful_rows')
                    ->label(__('app.labels.successful'))
                    ->numeric()
                    ->color('success'),

                Tables\Columns\TextColumn::make('failed_rows')
                    ->label(__('app.labels.failed'))
                    ->numeric()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('app.labels.created_by'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options([
                        'pending' => __('app.labels.pending'),
                        'processing' => __('app.labels.processing'),
                        'completed' => __('app.labels.completed'),
                        'failed' => __('app.labels.failed'),
                    ]),

                Tables\Filters\SelectFilter::make('model_type')
                    ->label(__('app.labels.model_type'))
                    ->options([
                        'Company' => __('app.labels.company'),
                        'People' => __('app.labels.people'),
                        'Lead' => __('app.labels.lead'),
                        'Opportunity' => __('app.labels.opportunity'),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('process')
                    ->label(__('app.actions.process'))
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (ImportJob $record): bool => $record->isPending())
                    ->action(function (ImportJob $record): void {
                        $importService = resolve(ImportService::class);
                        $importService->processImport($record);
                    })
                    ->requiresConfirmation(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (ImportJob $record): bool => $record->isPending()),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user']);
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
            'index' => Pages\ListImportJobs::route('/'),
            'create' => Pages\CreateImportJob::route('/create'),
            'view' => Pages\ViewImportJob::route('/{record}'),
            'edit' => Pages\EditImportJob::route('/{record}/edit'),
        ];
    }
}
