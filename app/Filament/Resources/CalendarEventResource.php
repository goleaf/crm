<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Enums\CalendarSyncStatus;
use App\Filament\Resources\CalendarEventResource\Pages\CreateCalendarEvent;
use App\Filament\Resources\CalendarEventResource\Pages\EditCalendarEvent;
use App\Filament\Resources\CalendarEventResource\Pages\ListCalendarEvents;
use App\Filament\Support\Filters\DateScopeFilter;
use App\Models\CalendarEvent;
use App\Models\Lead;
use App\Support\Helpers\ArrayHelper;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
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
            Section::make(__('app.labels.basic_information'))
                ->schema([
                    TextInput::make('title')
                        ->label(__('app.labels.title'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Select::make('type')
                        ->label(__('app.labels.type'))
                        ->options(CalendarEventType::class)
                        ->default(CalendarEventType::MEETING)
                        ->native(false)
                        ->live(),
                    Select::make('status')
                        ->label(__('app.labels.status'))
                        ->options(CalendarEventStatus::class)
                        ->default(CalendarEventStatus::SCHEDULED)
                        ->native(false),
                    Toggle::make('is_all_day')
                        ->label(__('app.labels.all_day'))
                        ->live(),
                ])
                ->columns(3),

            Section::make(__('app.labels.schedule'))
                ->schema([
                    DateTimePicker::make('start_at')
                        ->label(__('app.labels.starts'))
                        ->seconds(false)
                        ->required(),
                    DateTimePicker::make('end_at')
                        ->label(__('app.labels.ends'))
                        ->seconds(false),
                    TextInput::make('reminder_minutes_before')
                        ->label(__('app.labels.reminder_minutes_before'))
                        ->numeric()
                        ->minValue(0)
                        ->step(5)
                        ->suffix(__('app.labels.minutes')),
                ])
                ->columns(3),

            Section::make(__('app.labels.recurrence'))
                ->schema([
                    Select::make('recurrence_rule')
                        ->label(__('app.labels.recurrence_pattern'))
                        ->options([
                            'DAILY' => __('app.labels.daily'),
                            'WEEKLY' => __('app.labels.weekly'),
                            'MONTHLY' => __('app.labels.monthly'),
                            'YEARLY' => __('app.labels.yearly'),
                        ])
                        ->native(false)
                        ->live()
                        ->helperText(__('app.helpers.recurrence_pattern')),
                    DateTimePicker::make('recurrence_end_date')
                        ->label(__('app.labels.recurrence_end_date'))
                        ->seconds(false)
                        ->visible(fn (Get $get): bool => filled($get('recurrence_rule')))
                        ->helperText(__('app.helpers.recurrence_end_date')),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make(__('app.labels.location_details'))
                ->schema([
                    TextInput::make('location')
                        ->label(__('app.labels.location'))
                        ->maxLength(255),
                    TextInput::make('room_booking')
                        ->label(__('app.labels.room_booking'))
                        ->maxLength(255)
                        ->helperText(__('app.helpers.room_booking')),
                    TextInput::make('meeting_url')
                        ->label(__('app.labels.meeting_url'))
                        ->url()
                        ->maxLength(255)
                        ->helperText(__('app.helpers.video_conference_link')),
                ])
                ->columns(3),

            Section::make(__('app.labels.attendees'))
                ->schema([
                    Repeater::make('attendees')
                        ->schema([
                            TextInput::make('name')
                                ->label(__('app.labels.name'))
                                ->required(),
                            TextInput::make('email')
                                ->label(__('app.labels.email'))
                                ->email(),
                        ])
                        ->label(__('app.labels.attendees'))
                        ->defaultItems(0)
                        ->columns(2)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                ])
                ->collapsible(),

            Section::make(__('app.labels.meeting_details'))
                ->schema([
                    RichEditor::make('agenda')
                        ->label(__('app.labels.agenda'))
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'bulletList',
                            'orderedList',
                            'link',
                        ])
                        ->columnSpanFull(),
                    RichEditor::make('minutes')
                        ->label(__('app.labels.minutes'))
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'bulletList',
                            'orderedList',
                            'link',
                        ])
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label(__('app.labels.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->visible(fn (Get $get): bool => $get('type') === CalendarEventType::MEETING->value),
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
                TextColumn::make('related.name')
                    ->label(__('app.labels.lead'))
                    ->formatStateUsing(fn (mixed $state, CalendarEvent $record): string => $record->related instanceof Lead ? $record->related->name : '—')
                    ->toggleable(),
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
                    ->label(__('app.labels.location'))
                    ->toggleable(),
                TextColumn::make('room_booking')
                    ->label(__('app.labels.room_booking'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('recurrence_rule')
                    ->label(__('app.labels.recurrence'))
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (?string $state): string => $state ? __("app.labels.{$state}") : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('attendees')
                    ->label(__('app.labels.attendees'))
                    ->formatStateUsing(function (mixed $state): string {
                        if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            $state = json_last_error() === JSON_ERROR_NONE ? $decoded : [$state];
                        }

                        if (! is_array($state)) {
                            return in_array($state, [null, ''], true) ? '—' : (string) $state;
                        }

                        $names = ArrayHelper::pluck($state, 'name');

                        return ArrayHelper::joinList($names) ?? '—';
                    })
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sync_status')
                    ->label(__('app.labels.sync'))
                    ->badge()
                    ->formatStateUsing(fn (CalendarSyncStatus|string|null $state): string => $state instanceof CalendarSyncStatus ? $state->getLabel() : (CalendarSyncStatus::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state)))
                    ->color(fn (CalendarSyncStatus|string|null $state): string => $state instanceof CalendarSyncStatus ? $state->getColor() : (CalendarSyncStatus::tryFrom((string) $state)?->getColor() ?? 'gray'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_at', 'desc')
            ->filters([
                Filter::make('activity_filters')
                    ->label('Activity Filters')
                    ->form([
                        TextInput::make('title')
                            ->label(__('app.labels.title')),
                        Select::make('creator_id')
                            ->label(__('app.labels.created_by'))
                            ->relationship('creator', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('lead_id')
                            ->label(__('app.labels.lead'))
                            ->options(fn (): array => Lead::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload(),
                        DateTimePicker::make('schedule_from')
                            ->label('Schedule From')
                            ->seconds(false),
                        DateTimePicker::make('schedule_to')
                            ->label('Schedule To')
                            ->seconds(false),
                        DateTimePicker::make('created_from')
                            ->label('Created From')
                            ->seconds(false),
                        DateTimePicker::make('created_to')
                            ->label('Created To')
                            ->seconds(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['title'] ?? null, fn (Builder $query, string $title): Builder => $query->where('title', 'like', "%{$title}%"))
                            ->when($data['creator_id'] ?? null, fn (Builder $query, int $creatorId): Builder => $query->where('creator_id', $creatorId))
                            ->when(
                                $data['lead_id'] ?? null,
                                fn (Builder $query, int $leadId): Builder => $query
                                    ->where('related_type', Lead::class)
                                    ->where('related_id', $leadId),
                            )
                            ->when($data['schedule_from'] ?? null, fn (Builder $query, string $from): Builder => $query->where('start_at', '>=', $from))
                            ->when($data['schedule_to'] ?? null, fn (Builder $query, string $to): Builder => $query->where('start_at', '<=', $to))
                            ->when($data['created_from'] ?? null, fn (Builder $query, string $from): Builder => $query->where('created_at', '>=', $from))
                            ->when($data['created_to'] ?? null, fn (Builder $query, string $to): Builder => $query->where('created_at', '<=', $to));
                    }),
                DateScopeFilter::make(name: 'start_at_range', column: 'start_at'),
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
                    ->query(fn (Builder $query): Builder => $query->where('start_at', '>=', today())),
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
            ->with([
                'creator:id,name',
                'team:id,name',
                'related',
                'recurrenceParent:id,title,recurrence_rule',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
