<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Enums\InvoiceReminderType;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class RemindersRelationManager extends RelationManager
{
    protected static string $relationship = 'reminders';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-bell-alert';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('reminder_type')
                    ->label('Type')
                    ->options(InvoiceReminderType::options())
                    ->enum(InvoiceReminderType::class)
                    ->required()
                    ->native(false),
                \Filament\Forms\Components\DateTimePicker::make('remind_at')
                    ->label('Remind At')
                    ->required(),
                \Filament\Forms\Components\DateTimePicker::make('sent_at')
                    ->label('Sent At'),
                \Filament\Forms\Components\TextInput::make('channel')
                    ->label('Channel')
                    ->placeholder('email, sms, slack'),
                \Filament\Forms\Components\Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                BadgeColumn::make('reminder_type')
                    ->label('Type')
                    ->colors([
                        'info' => InvoiceReminderType::DUE_SOON->value,
                        'danger' => InvoiceReminderType::OVERDUE->value,
                        'gray' => InvoiceReminderType::CUSTOM->value,
                    ])
                    ->formatStateUsing(fn (InvoiceReminderType $state): string => $state->label()),
                TextColumn::make('remind_at')
                    ->label('Remind At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('channel')
                    ->label('Channel')
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
