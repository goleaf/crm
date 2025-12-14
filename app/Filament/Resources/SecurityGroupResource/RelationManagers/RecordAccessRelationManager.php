<?php

declare(strict_types=1);

namespace App\Filament\Resources\SecurityGroupResource\RelationManagers;

use App\Models\SecurityGroupRecordAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

final class RecordAccessRelationManager extends RelationManager
{
    protected static string $relationship = 'recordAccess';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('record_type')
                    ->label(__('app.labels.record_type'))
                    ->options([
                        \App\Models\Company::class => __('app.labels.companies'),
                        \App\Models\People::class => __('app.labels.people'),
                        \App\Models\Opportunity::class => __('app.labels.opportunities'),
                        \App\Models\Task::class => __('app.labels.tasks'),
                        \App\Models\Note::class => __('app.labels.notes'),
                    ])
                    ->required()
                    ->live()
                    ->disabled(fn (?string $operation): bool => $operation === 'edit'),
                Forms\Components\Select::make('record_id')
                    ->label(__('app.labels.record'))
                    ->options(function (Forms\Get $get): array {
                        $recordType = $get('record_type');
                        if (! $recordType || ! class_exists($recordType)) {
                            return [];
                        }

                        return $recordType::limit(100)->pluck('name', 'id')->toArray();
                    })
                    ->searchable()
                    ->required()
                    ->disabled(fn (?string $operation): bool => $operation === 'edit'),
                Forms\Components\Select::make('access_level')
                    ->label(__('app.labels.access_level'))
                    ->options([
                        'none' => __('app.labels.no_access'),
                        'read' => __('app.labels.read_only'),
                        'write' => __('app.labels.read_write'),
                        'admin' => __('app.labels.admin_access'),
                        'owner' => __('app.labels.owner_access'),
                    ])
                    ->default('read')
                    ->required(),
                Forms\Components\KeyValue::make('field_permissions')
                    ->label(__('app.labels.field_permissions'))
                    ->keyLabel(__('app.labels.field'))
                    ->valueLabel(__('app.labels.permission'))
                    ->helperText(__('app.helpers.field_level_permissions')),
                Forms\Components\Toggle::make('inherit_from_parent')
                    ->label(__('app.labels.inherit_from_parent'))
                    ->default(true)
                    ->helperText(__('app.helpers.inherit_parent_permissions')),
                Forms\Components\KeyValue::make('permission_overrides')
                    ->label(__('app.labels.permission_overrides'))
                    ->keyLabel(__('app.labels.permission'))
                    ->valueLabel(__('app.labels.value'))
                    ->helperText(__('app.helpers.permission_overrides')),
                Forms\Components\Textarea::make('notes')
                    ->label(__('app.labels.notes'))
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('record_type')
            ->columns([
                Tables\Columns\TextColumn::make('record_type')
                    ->label(__('app.labels.record_type'))
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge()
                    ->color(fn (string $state): string => match (class_basename($state)) {
                        'Company' => 'primary',
                        'People' => 'success',
                        'Opportunity' => 'warning',
                        'Task' => 'info',
                        'Note' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('record_id')
                    ->label(__('app.labels.record_id'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('record_name')
                    ->label(__('app.labels.record_name'))
                    ->getStateUsing(function (SecurityGroupRecordAccess $record): string {
                        try {
                            $model = $record->record;

                            return $model?->name ?? $model?->title ?? "Record #{$record->record_id}";
                        } catch (\Exception) {
                            return "Record #{$record->record_id}";
                        }
                    }),
                Tables\Columns\TextColumn::make('access_level')
                    ->label(__('app.labels.access_level'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'none' => 'gray',
                        'read' => 'info',
                        'write' => 'warning',
                        'admin' => 'success',
                        'owner' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'none' => __('app.labels.no_access'),
                        'read' => __('app.labels.read_only'),
                        'write' => __('app.labels.read_write'),
                        'admin' => __('app.labels.admin_access'),
                        'owner' => __('app.labels.owner_access'),
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('inherit_from_parent')
                    ->label(__('app.labels.inherit'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('assignedBy.name')
                    ->label(__('app.labels.assigned_by'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('assigned_at')
                    ->label(__('app.labels.assigned_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('record_type')
                    ->label(__('app.labels.record_type'))
                    ->options([
                        \App\Models\Company::class => __('app.labels.companies'),
                        \App\Models\People::class => __('app.labels.people'),
                        \App\Models\Opportunity::class => __('app.labels.opportunities'),
                        \App\Models\Task::class => __('app.labels.tasks'),
                        \App\Models\Note::class => __('app.labels.notes'),
                    ]),
                Tables\Filters\SelectFilter::make('access_level')
                    ->label(__('app.labels.access_level'))
                    ->options([
                        'none' => __('app.labels.no_access'),
                        'read' => __('app.labels.read_only'),
                        'write' => __('app.labels.read_write'),
                        'admin' => __('app.labels.admin_access'),
                        'owner' => __('app.labels.owner_access'),
                    ]),
                Tables\Filters\TernaryFilter::make('inherit_from_parent')
                    ->label(__('app.labels.inherits_permissions')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['assigned_by'] = auth()->id();
                        $data['assigned_at'] = now();

                        return $data;
                    })
                    ->after(function (): void {
                        Notification::make()
                            ->title(__('app.notifications.record_access_granted'))
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function (): void {
                        Notification::make()
                            ->title(__('app.notifications.record_access_updated'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function (): void {
                        Notification::make()
                            ->title(__('app.notifications.record_access_revoked'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('view_record')
                    ->label(__('app.actions.view_record'))
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(function (SecurityGroupRecordAccess $record): ?string {
                        try {
                            $model = $record->record;
                            if (! $model) {
                                return null;
                            }

                            // Generate URL based on record type
                            return match (class_basename($record->record_type)) {
                                'Company' => route('filament.admin.resources.companies.view', $model),
                                'People' => route('filament.admin.resources.people.view', $model),
                                'Opportunity' => route('filament.admin.resources.opportunities.view', $model),
                                'Task' => route('filament.admin.resources.tasks.view', $model),
                                'Note' => route('filament.admin.resources.notes.view', $model),
                                default => null,
                            };
                        } catch (\Exception) {
                            return null;
                        }
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('change_access_level')
                        ->label(__('app.actions.change_access_level'))
                        ->icon('heroicon-o-key')
                        ->color('primary')
                        ->form([
                            Forms\Components\Select::make('access_level')
                                ->label(__('app.labels.new_access_level'))
                                ->options([
                                    'none' => __('app.labels.no_access'),
                                    'read' => __('app.labels.read_only'),
                                    'write' => __('app.labels.read_write'),
                                    'admin' => __('app.labels.admin_access'),
                                    'owner' => __('app.labels.owner_access'),
                                ])
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            $records->each->update(['access_level' => $data['access_level']]);

                            Notification::make()
                                ->title(__('app.notifications.access_levels_updated'))
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
