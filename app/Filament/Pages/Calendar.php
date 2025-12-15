<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Models\CalendarEvent;
use App\Notifications\RealTimeFilamentNotification;
use App\Services\ZapScheduleService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Zap\Exceptions\InvalidScheduleException;
use Zap\Exceptions\ScheduleConflictException;

final class Calendar extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected string $view = 'filament.pages.calendar';

    protected static ?int $navigationSort = 15;

    public string $view_mode = 'month';

    public string $current_date;

    public array $filters = [
        'types' => [],
        'statuses' => [],
        'search' => '',
        'team_members' => [],
        'show_team_events' => true,
    ];

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.calendar');
    }

    public function getTitle(): string
    {
        return __('app.navigation.calendar');
    }

    public function mount(): void
    {
        $this->current_date = now()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_event')
                ->label(__('app.actions.create_event'))
                ->icon('heroicon-o-plus')
                ->form($this->getEventFormSchema())
                ->action(function (array $data): void {
                    $user = auth()->user();
                    $team = $user?->currentTeam;

                    try {
                        DB::transaction(function () use ($data, $team, $user): void {
                            $event = CalendarEvent::create([
                                ...$data,
                                'team_id' => $team?->getKey(),
                                'creator_id' => $user?->getKey(),
                            ]);

                            $this->getZapScheduleService()->syncCalendarEventSchedule($event);
                        });

                        $this->broadcastNotification(__('app.messages.event_created'));

                        $this->dispatch('event-created');
                    } catch (ScheduleConflictException|InvalidScheduleException $exception) {
                        Notification::make()
                            ->title(__('app.messages.schedule_conflict'))
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalWidth('3xl'),

            Action::make('export_ical')
                ->label(__('app.actions.export_ical'))
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (): string => route('calendar.export.ical'))
                ->openUrlInNewTab(),
        ];
    }

    public function changeView(string $mode): void
    {
        $this->view_mode = $mode;
    }

    public function previousPeriod(): void
    {
        $date = \Illuminate\Support\Facades\Date::parse($this->current_date);

        $this->current_date = match ($this->view_mode) {
            'day' => $date->subDay()->toDateString(),
            'week' => $date->subWeek()->toDateString(),
            'month' => $date->subMonth()->toDateString(),
            'year' => $date->subYear()->toDateString(),
            default => $date->subMonth()->toDateString(),
        };
    }

    public function nextPeriod(): void
    {
        $date = \Illuminate\Support\Facades\Date::parse($this->current_date);

        $this->current_date = match ($this->view_mode) {
            'day' => $date->addDay()->toDateString(),
            'week' => $date->addWeek()->toDateString(),
            'month' => $date->addMonth()->toDateString(),
            'year' => $date->addYear()->toDateString(),
            default => $date->addMonth()->toDateString(),
        };
    }

    public function today(): void
    {
        $this->current_date = now()->toDateString();
    }

    /**
     * Cached team members to avoid repeated queries.
     */
    private ?Collection $cachedTeamMembers = null;

    public function getTeamMembers(): Collection
    {
        // Cache team members to avoid repeated queries on every render
        if ($this->cachedTeamMembers instanceof \Illuminate\Support\Collection) {
            return $this->cachedTeamMembers;
        }

        $team = auth()->user()?->currentTeam;

        if (! $team) {
            return $this->cachedTeamMembers = collect();
        }

        // Get all team members including owner - select only needed columns
        $members = $team->users()->select(['users.id', 'users.name'])->get();

        // Add team owner if not already in the list
        if ($team->owner && ! $members->contains('id', $team->owner->id)) {
            $members->push($team->owner);
        }

        return $this->cachedTeamMembers = $members;
    }

    public function getEvents(): Collection
    {
        $date = \Illuminate\Support\Facades\Date::parse($this->current_date);
        $team = auth()->user()?->currentTeam;
        $user = auth()->user();

        [$start, $end] = match ($this->view_mode) {
            'day' => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
            'week' => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
            'month' => [$date->copy()->startOfMonth()->startOfWeek(), $date->copy()->endOfMonth()->endOfWeek()],
            'year' => [$date->copy()->startOfYear(), $date->copy()->endOfYear()],
            default => [$date->copy()->startOfMonth()->startOfWeek(), $date->copy()->endOfMonth()->endOfWeek()],
        };

        // Use optimized query scopes for better performance and readability
        return CalendarEvent::query()
            ->when($team && $this->filters['show_team_events'], fn ($query) => $query->forTeam($team->getKey()))
            ->when(! $this->filters['show_team_events'], fn ($query) => $query->where('creator_id', $user?->getKey()))
            ->inDateRange($start, $end)
            ->when(! empty($this->filters['types']), fn ($query) => $query->ofTypes($this->filters['types']))
            ->when(! empty($this->filters['statuses']), fn ($query) => $query->withStatuses($this->filters['statuses']))
            ->when(! empty($this->filters['team_members']), fn ($query) => $query->whereIn('creator_id', $this->filters['team_members']))
            ->when(! empty($this->filters['search']), fn ($query) => $query->search($this->filters['search']))
            ->withCommonRelations()
            ->orderBy('start_at')
            ->get();
    }

    /**
     * Surface Zap bookable slots for the current date.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBookableSlots(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        return array_slice(
            $this->getZapScheduleService()->bookableSlotsForDate($user, $this->current_date),
            0,
            8,
        );
    }

    public function getNextBookableSlot(): ?array
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        return $this->getZapScheduleService()->nextBookableSlot($user, $this->current_date);
    }

    public function updateEvent(int $eventId, string $newStart, ?string $newEnd = null): void
    {
        $event = CalendarEvent::findOrFail($eventId);

        $this->authorize('update', $event);

        try {
            DB::transaction(function () use ($event, $newEnd, $newStart): void {
                $event->update([
                    'start_at' => \Illuminate\Support\Facades\Date::parse($newStart),
                    'end_at' => $newEnd ? \Illuminate\Support\Facades\Date::parse($newEnd) : $event->end_at,
                ]);

                $this->getZapScheduleService()->syncCalendarEventSchedule($event);
            });
        } catch (ScheduleConflictException|InvalidScheduleException $exception) {
            Notification::make()
                ->title(__('app.messages.schedule_conflict'))
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->broadcastNotification(__('app.messages.event_updated'));

        $this->dispatch('event-updated');
    }

    private function broadcastNotification(string $title, ?string $body = null): void
    {
        $notification = Notification::make()
            ->title($title)
            ->success();

        if ($body) {
            $notification->body($body);
        }

        $notification->send();

        if (($user = auth()->user()) !== null) {
            $user->notify(new RealTimeFilamentNotification($notification));
        }
    }

    private function getEventFormSchema(): array
    {
        return [
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
                        ->required()
                        ->default(now()),
                    DateTimePicker::make('end_at')
                        ->label(__('app.labels.ends'))
                        ->seconds(false)
                        ->default(now()->addHour()),
                    TextInput::make('reminder_minutes_before')
                        ->label(__('app.labels.reminder_minutes_before'))
                        ->numeric()
                        ->minValue(0)
                        ->step(5)
                        ->suffix(__('app.labels.minutes')),
                ])
                ->columns(3),

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
                    Textarea::make('notes')
                        ->label(__('app.labels.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->visible(fn (Get $get): bool => $get('type') === CalendarEventType::MEETING->value),
        ];
    }

    private function getZapScheduleService(): ZapScheduleService
    {
        return resolve(ZapScheduleService::class);
    }
}
