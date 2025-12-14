<?php

declare(strict_types=1);

namespace App\Filament\Resources\SecurityGroupResource\RelationManagers;

use App\Models\SecurityGroupAuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

final class AuditLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('action')
                    ->label(__('app.labels.action'))
                    ->disabled(),
                Forms\Components\TextInput::make('entity_type')
                    ->label(__('app.labels.entity_type'))
                    ->disabled(),
                Forms\Components\TextInput::make('entity_id')
                    ->label(__('app.labels.entity_id'))
                    ->disabled(),
                Forms\Components\KeyValue::make('old_values')
                    ->label(__('app.labels.old_values'))
                    ->disabled(),
                Forms\Components\KeyValue::make('new_values')
                    ->label(__('app.labels.new_values'))
                    ->disabled(),
                Forms\Components\KeyValue::make('metadata')
                    ->label(__('app.labels.metadata'))
                    ->disabled(),
                Forms\Components\Textarea::make('notes')
                    ->label(__('app.labels.notes'))
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('action')
            ->columns([
                Tables\Columns\TextColumn::make('action')
                    ->label(__('app.labels.action'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'member_added' => 'info',
                        'member_removed' => 'gray',
                        'record_access_granted' => 'success',
                        'record_access_revoked' => 'danger',
                        'broadcast_sent' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (SecurityGroupAuditLog $record): string => $record->action_description),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('app.labels.user'))
                    ->placeholder(__('app.labels.system')),
                Tables\Columns\TextColumn::make('entity_type')
                    ->label(__('app.labels.entity'))
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : __('app.labels.group'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('entity_id')
                    ->label(__('app.labels.entity_id'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('app.labels.ip_address'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.timestamp'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label(__('app.labels.action'))
                    ->options([
                        'created' => __('app.labels.created'),
                        'updated' => __('app.labels.updated'),
                        'deleted' => __('app.labels.deleted'),
                        'member_added' => __('app.labels.member_added'),
                        'member_removed' => __('app.labels.member_removed'),
                        'member_updated' => __('app.labels.member_updated'),
                        'record_access_granted' => __('app.labels.access_granted'),
                        'record_access_revoked' => __('app.labels.access_revoked'),
                        'broadcast_sent' => __('app.labels.broadcast_sent'),
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label(__('app.labels.user'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('app.labels.from_date')),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('app.labels.until_date')),
                    ])
                    ->query(function ($query, array $data): void {
                        $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(__('app.modals.audit_log_details'))
                    ->modalContent(fn (SecurityGroupAuditLog $record): \Illuminate\Contracts\View\View => view('filament.modals.audit-log-details', [
                        'auditLog' => $record,
                        'changes' => $record->changes,
                    ])),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public function isReadOnly(): bool
    {
        return true; // Audit logs should be read-only
    }
}
