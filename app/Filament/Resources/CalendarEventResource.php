<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Enums\CalendarSyncStatus;
use App\Filament\Resources\CalendarEventResource\Pages\CreateCalendarEvent;
use App\Filament\Resources\CalendarEventResource\Pages\EditCalendarEvent;
use App\Filament\Resources\CalendarEventResource\Pages\ListCalendarEvents;
use App\Models\CalendarEvent;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

final class CalendarEventResource extends Resource
{
    protected static ?string $model = CalendarEvent::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label(__('app.labels.title'))
                ->required()
                ->maxLength(255),
            Select::make('type')
                ->label('Type')
                ->options(CalendarEventType::class)
                ->default(CalendarEventType::MEETING)
                ->native(false),
            Select::make('status')
                ->label(__('app.labels.status'))
                ->options(CalendarEventStatus::class)
                ->default(CalendarEventStatus::SCHEDULED)
                ->native(false),
            Toggle::make('is_all_day')
                ->label('All day'),
            DateTimePicker::make('start_at')
                ->label('Starts')
                ->seconds(false)
                ->required(),
            DateTimePicker::make('end_at')
                ->label('Ends')
                ->seconds(false),
            TextInput::make('location')
                ->maxLength(255),
            TextInput::make('meeting_url')
                ->label('Meeting URL')
                ->url()
                ->maxLength(255),
            TextInput::make('reminder_minutes_before')
                ->label('Reminder (minutes before)')
                ->numeric()
                ->minValue(0)
                ->step(5),
            Repeater::make('attendees')
                ->schema([
                    TextInput::make('name')->required(),
                    TextInput::make('email')->email(),
                ])
                ->label('Attendees')
                ->defaultItems(0)
                ->columns(2),
            Textarea::make('notes')
                ->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('app.labels.title'))
                    ->searchable()
                    ->wrap(),
                BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (CalendarEventType|string|null $state): string => $state instanceof CalendarEventType ? $state->getLabel() : (CalendarEventType::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state)))
                    ->color('gray'),
                BadgeColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->formatStateUsing(fn (CalendarEventStatus|string|null $state): string => $state instanceof CalendarEventStatus ? $state->getLabel() : (CalendarEventStatus::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state)))
                    ->colors([
                        'info' => CalendarEventStatus::SCHEDULED->value,
                        'primary' => CalendarEventStatus::CONFIRMED->value,
                        'success' => CalendarEventStatus::COMPLETED->value,
                        'danger' => CalendarEventStatus::CANCELLED->value,
                    ]),
                TextColumn::make('start_at')
                    ->label('Starts')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('Ends')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location')
                    ->toggleable(),
                TextColumn::make('sync_status')
                    ->label('Sync')
                    ->badge()
                    ->formatStateUsing(fn (CalendarSyncStatus|string|null $state): string => $state instanceof CalendarSyncStatus ? $state->getLabel() : (CalendarSyncStatus::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state)))
                    ->color(fn (CalendarSyncStatus|string|null $state): string => $state instanceof CalendarSyncStatus ? $state->getColor() : (CalendarSyncStatus::tryFrom((string) $state)?->getColor() ?? 'gray'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options(CalendarEventStatus::class)
                    ->multiple(),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(CalendarEventType::class)
                    ->multiple(),
                Filter::make('upcoming')
                    ->label('Upcoming only')
                    ->query(fn (Builder $query): Builder => $query->where('start_at', '>=', now()->startOfDay())),
                Filter::make('synced')
                    ->label('Synced')
                    ->query(fn (Builder $query): Builder => $query->where('sync_status', CalendarSyncStatus::SYNCED)),
                Filter::make('all_day')
                    ->label('All day')
                    ->query(fn (Builder $query): Builder => $query->where('is_all_day', true)),
                TrashedFilter::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCalendarEvents::route('/'),
            'create' => CreateCalendarEvent::route('/create'),
            'edit' => EditCalendarEvent::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<CalendarEvent>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
