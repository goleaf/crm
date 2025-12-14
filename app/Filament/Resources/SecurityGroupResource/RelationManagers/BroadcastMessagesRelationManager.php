<?php

declare(strict_types=1);

namespace App\Filament\Resources\SecurityGroupResource\RelationManagers;

use App\Models\SecurityGroupBroadcastMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

final class BroadcastMessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'broadcastMessages';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('subject')
                    ->label(__('app.labels.subject'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('message')
                    ->label(__('app.labels.message'))
                    ->required()
                    ->rows(4),
                Forms\Components\Select::make('priority')
                    ->label(__('app.labels.priority'))
                    ->options([
                        'low' => __('app.labels.low'),
                        'normal' => __('app.labels.normal'),
                        'high' => __('app.labels.high'),
                        'urgent' => __('app.labels.urgent'),
                    ])
                    ->default('normal')
                    ->required(),
                Forms\Components\Toggle::make('include_subgroups')
                    ->label(__('app.labels.include_subgroups'))
                    ->default(true)
                    ->helperText(__('app.helpers.include_subgroups')),
                Forms\Components\Toggle::make('require_acknowledgment')
                    ->label(__('app.labels.require_acknowledgment'))
                    ->helperText(__('app.helpers.require_acknowledgment')),
                Forms\Components\DateTimePicker::make('scheduled_at')
                    ->label(__('app.labels.scheduled_at'))
                    ->helperText(__('app.helpers.schedule_message'))
                    ->nullable(),
                Forms\Components\Select::make('status')
                    ->label(__('app.labels.status'))
                    ->options([
                        'draft' => __('app.labels.draft'),
                        'scheduled' => __('app.labels.scheduled'),
                        'sent' => __('app.labels.sent'),
                        'cancelled' => __('app.labels.cancelled'),
                    ])
                    ->default('draft')
                    ->required()
                    ->disabled(fn (?string $operation): bool => $operation === 'create'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                Tables\Columns\TextColumn::make('subject')
                    ->label(__('app.labels.subject'))
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('priority')
                    ->label(__('app.labels.priority'))
                    ->badge()
                    ->color(fn (SecurityGroupBroadcastMessage $record): string => $record->priority_color),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (SecurityGroupBroadcastMessage $record): string => $record->status_color),
                Tables\Columns\IconColumn::make('include_subgroups')
                    ->label(__('app.labels.subgroups'))
                    ->boolean(),
                Tables\Columns\IconColumn::make('require_acknowledgment')
                    ->label(__('app.labels.ack_required'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('sender.name')
                    ->label(__('app.labels.sender')),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label(__('app.labels.scheduled'))
                    ->dateTime()
                    ->placeholder(__('app.labels.immediate'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label(__('app.labels.sent_at'))
                    ->dateTime()
                    ->placeholder(__('app.labels.not_sent'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('acknowledgments_count')
                    ->label(__('app.labels.acknowledgments'))
                    ->counts('acknowledgments')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options([
                        'draft' => __('app.labels.draft'),
                        'scheduled' => __('app.labels.scheduled'),
                        'sent' => __('app.labels.sent'),
                        'cancelled' => __('app.labels.cancelled'),
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->label(__('app.labels.priority'))
                    ->options([
                        'low' => __('app.labels.low'),
                        'normal' => __('app.labels.normal'),
                        'high' => __('app.labels.high'),
                        'urgent' => __('app.labels.urgent'),
                    ]),
                Tables\Filters\TernaryFilter::make('require_acknowledgment')
                    ->label(__('app.labels.requires_acknowledgment')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['sender_id'] = auth()->id();

                        // Set status based on scheduled_at
                        if (! empty($data['scheduled_at'])) {
                            $data['status'] = 'scheduled';
                        }

                        return $data;
                    })
                    ->after(function (): void {
                        Notification::make()
                            ->title(__('app.notifications.broadcast_message_created'))
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(__('app.modals.broadcast_message_details'))
                    ->modalContent(fn (SecurityGroupBroadcastMessage $record): \Illuminate\Contracts\View\View => view('filament.modals.broadcast-message-details', [
                        'message' => $record,
                        'deliveryStats' => $record->delivery_stats,
                        'acknowledgments' => $record->acknowledgments()->with('user')->get(),
                    ])),
                Tables\Actions\EditAction::make()
                    ->visible(fn (SecurityGroupBroadcastMessage $record): bool => in_array($record->status, ['draft', 'scheduled']),
                    ),
                Tables\Actions\Action::make('send')
                    ->label(__('app.actions.send_now'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (SecurityGroupBroadcastMessage $record): void {
                        $record->send();

                        Notification::make()
                            ->title(__('app.notifications.broadcast_message_sent'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SecurityGroupBroadcastMessage $record): bool => in_array($record->status, ['draft', 'scheduled']),
                    ),
                Tables\Actions\Action::make('cancel')
                    ->label(__('app.actions.cancel'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (SecurityGroupBroadcastMessage $record): void {
                        $record->cancel();

                        Notification::make()
                            ->title(__('app.notifications.broadcast_message_cancelled'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SecurityGroupBroadcastMessage $record): bool => $record->status === 'scheduled',
                    ),
                Tables\Actions\Action::make('view_acknowledgments')
                    ->label(__('app.actions.view_acknowledgments'))
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->modalHeading(__('app.modals.message_acknowledgments'))
                    ->modalContent(fn (SecurityGroupBroadcastMessage $record): \Illuminate\Contracts\View\View => view('filament.modals.message-acknowledgments', [
                        'message' => $record,
                        'acknowledgments' => $record->acknowledgments()->with('user')->get(),
                        'totalRecipients' => $record->getTotalRecipients(),
                    ]))
                    ->visible(fn (SecurityGroupBroadcastMessage $record): bool => $record->require_acknowledgment && $record->status === 'sent',
                    ),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (SecurityGroupBroadcastMessage $record): bool => in_array($record->status, ['draft', 'cancelled']),
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('send_selected')
                        ->label(__('app.actions.send_selected'))
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $sent = 0;
                            foreach ($records as $record) {
                                if (in_array($record->status, ['draft', 'scheduled'])) {
                                    $record->send();
                                    $sent++;
                                }
                            }

                            Notification::make()
                                ->title(__('app.notifications.broadcast_messages_sent'))
                                ->body(__('app.notifications.messages_sent_count', ['count' => $sent]))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('cancel_selected')
                        ->label(__('app.actions.cancel_selected'))
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $cancelled = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'scheduled') {
                                    $record->cancel();
                                    $cancelled++;
                                }
                            }

                            Notification::make()
                                ->title(__('app.notifications.broadcast_messages_cancelled'))
                                ->body(__('app.notifications.messages_cancelled_count', ['count' => $cancelled]))
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
