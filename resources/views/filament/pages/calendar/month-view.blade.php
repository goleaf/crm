@php
    $date = \Carbon\Carbon::parse($current_date);
    $startOfMonth = $date->copy()->startOfMonth();
    $endOfMonth = $date->copy()->endOfMonth();
    $startOfCalendar = $startOfMonth->copy()->startOfWeek();
    $endOfCalendar = $endOfMonth->copy()->endOfWeek();
    
    $events = $this->getEvents();
    $eventsByDate = $events->groupBy(fn($event) => $event->start_at->toDateString());
@endphp

<div class="calendar-month-view">
    {{-- Weekday Headers --}}
    <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700">
        @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
            <div class="p-2 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ __("app.labels.{$day}") }}
            </div>
        @endforeach
    </div>

    {{-- Calendar Grid --}}
    <div class="grid grid-cols-7">
        @php
            $currentDay = $startOfCalendar->copy();
        @endphp

        @while($currentDay <= $endOfCalendar)
            @php
                $isCurrentMonth = $currentDay->month === $date->month;
                $isToday = $currentDay->isToday();
                $dateString = $currentDay->toDateString();
                $dayEvents = $eventsByDate->get($dateString, collect());
            @endphp

            <div 
                class="min-h-[120px] border-r border-b border-gray-200 dark:border-gray-700 p-2 {{ !$isCurrentMonth ? 'bg-gray-50 dark:bg-gray-900' : '' }}"
                wire:key="day-{{ $dateString }}"
            >
                <div class="flex justify-between items-start mb-1">
                    <span class="text-sm font-medium {{ $isToday ? 'bg-primary-500 text-white rounded-full w-6 h-6 flex items-center justify-center' : ($isCurrentMonth ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400') }}">
                        {{ $currentDay->day }}
                    </span>
                </div>

                <div class="space-y-1">
                    @foreach($dayEvents->take(3) as $event)
                        <div 
                            class="text-xs p-1 rounded cursor-pointer hover:opacity-80 transition"
                            style="background-color: {{ $event->type->getColor() }}20; border-left: 3px solid {{ $event->type->getColor() }}"
                            wire:click="$dispatch('open-modal', { id: 'view-event-{{ $event->id }}' })"
                        >
                            <div class="font-medium truncate">{{ $event->title }}</div>
                            @if(!$event->is_all_day)
                                <div class="text-gray-600 dark:text-gray-400">
                                    {{ $event->start_at->format('g:i A') }}
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @if($dayEvents->count() > 3)
                        <div class="text-xs text-gray-500 dark:text-gray-400 pl-1">
                            +{{ $dayEvents->count() - 3 }} {{ __('app.labels.more') }}
                        </div>
                    @endif
                </div>
            </div>

            @php
                $currentDay->addDay();
            @endphp
        @endwhile
    </div>
</div>
