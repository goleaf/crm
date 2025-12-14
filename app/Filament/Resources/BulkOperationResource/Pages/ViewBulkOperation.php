<?php

declare(strict_types=1);

namespace App\Filament\Resources\BulkOperationResource\Pages;

use App\Filament\Resources\BulkOperationResource;
use App\Models\BulkOperation;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\KeyValueEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;

final class ViewBulkOperation extends ViewRecord
{
    protected static string $resource = BulkOperationResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.sections.operation_details'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('type')
                                    ->label(__('app.labels.operation_type'))
                                    ->badge(),

                                TextEntry::make('model_type')
                                    ->label(__('app.labels.model_type'))
                                    ->formatStateUsing(fn (string $state): string => class_basename($state),
                                    ),

                                TextEntry::make('status')
                                    ->label(__('app.labels.status'))
                                    ->badge(),
                            ]),
                    ]),

                Section::make(__('app.sections.progress'))
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_records')
                                    ->label(__('app.labels.total_records'))
                                    ->numeric(),

                                TextEntry::make('processed_records')
                                    ->label(__('app.labels.processed_records'))
                                    ->numeric()
                                    ->color('success'),

                                TextEntry::make('failed_records')
                                    ->label(__('app.labels.failed_records'))
                                    ->numeric()
                                    ->color('danger'),

                                TextEntry::make('progress_percentage')
                                    ->label(__('app.labels.progress'))
                                    ->formatStateUsing(fn (float $state): string => $state . '%')
                                    ->color(fn (float $state): string => match (true) {
                                        $state === 100.0 => 'success',
                                        $state >= 50.0 => 'warning',
                                        default => 'gray',
                                    }),
                            ]),
                    ]),

                Section::make(__('app.sections.configuration'))
                    ->schema([
                        TextEntry::make('batch_size')
                            ->label(__('app.labels.batch_size'))
                            ->numeric(),

                        KeyValueEntry::make('operation_data')
                            ->label(__('app.labels.operation_data'))
                            ->visible(fn (BulkOperation $record): bool => ! empty($record->operation_data),
                            ),
                    ]),

                Section::make(__('app.sections.timing'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('started_at')
                                    ->label(__('app.labels.started_at'))
                                    ->dateTime()
                                    ->placeholder(__('app.placeholders.not_started')),

                                TextEntry::make('completed_at')
                                    ->label(__('app.labels.completed_at'))
                                    ->dateTime()
                                    ->placeholder(__('app.placeholders.not_completed')),

                                TextEntry::make('duration')
                                    ->label(__('app.labels.duration'))
                                    ->getStateUsing(fn (BulkOperation $record): ?string => $record->duration ? gmdate('H:i:s', $record->duration) : null,
                                    )
                                    ->placeholder(__('app.placeholders.not_available')),
                            ]),
                    ]),

                Section::make(__('app.sections.metadata'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('createdBy.name')
                                    ->label(__('app.labels.created_by')),

                                TextEntry::make('team.name')
                                    ->label(__('app.labels.team')),

                                TextEntry::make('created_at')
                                    ->label(__('app.labels.created_at'))
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make(__('app.sections.errors'))
                    ->schema([
                        TextEntry::make('errors')
                            ->label(__('app.labels.errors'))
                            ->formatStateUsing(fn (?array $state): string => $state ? implode("\n", $state) : __('app.placeholders.no_errors'),
                            )
                            ->color('danger'),
                    ])
                    ->visible(fn (BulkOperation $record): bool => ! empty($record->errors),
                    )
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->label(__('app.actions.cancel_operation'))
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn (BulkOperation $record): bool => $record->status === \App\Enums\BulkOperationStatus::PROCESSING,
                )
                ->action(function (BulkOperation $record): void {
                    $record->update([
                        'status' => \App\Enums\BulkOperationStatus::CANCELLED,
                        'completed_at' => now(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title(__('app.notifications.bulk_operation_cancelled'))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading(__('app.modals.cancel_bulk_operation'))
                ->modalDescription(__('app.modals.cancel_bulk_operation_description')),

            Actions\DeleteAction::make()
                ->visible(fn (BulkOperation $record): bool => $record->is_completed,
                ),
        ];
    }
}
