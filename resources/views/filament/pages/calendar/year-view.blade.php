@php
    $date = \Carbon\Carbon::parse($current_date);
    $year = $date->year;
    $events = $this->getEvents();
    $eventsByMonth = $events->groupBy(fn($event) => $event->start_at->format('Y-m'));
@endphp

<div class="calendar-year-view p-4">
    <div class="text-center mb-6">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $year }}</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @for($month = 1; $month <= 12; $month++)
            @php
                $monthDate = \Carbon\Carbon::create($year, $month, 1);
                $monthKey = $monthDate->format('Y-m');
                $monthEvents = $eventsByMonth->get($monthKey, collect());
                $startOfMonth = $monthDate->copy()->startOfMonth();
                $endOfMonth = $monthDate->copy()->endOfMonth();
                $startOfCalendar = $startOfMonth->copy()->startOfWeek();
                $endOfCalendar = $endOfMonth->copy()->endOfWeek();
            @endphp

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                {{-- Month Header --}}
                <div class="text-center mb-2">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ $monthDate->format('F') }}
                    </h3>
                    @if($monthEvents->count() > 0)
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $monthEvents->count() }} {{ __('app.labels.events') }}
                        </div>
                    @endif
                </div>

                {{-- Mini Calendar --}}
                <div class="grid grid-cols-7 gap-1">
                    {{-- Weekday Headers --}}
                    @foreach(['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $day)
                        <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400">
                            {{ $day }}
                        </div>
                    @endforeach

                    {{-- Days --}}
                    @php
                        $currentDay = $startOfCalendar->copy();
                    @endphp

                    @while($currentDay <= $endOfCalendar)
                        @php
                            $isCurrentMonth = $currentDay->month === $month;
                            $isToday = $currentDay->isToday();
                            $dateString = $currentDay->toDateString();
                            $dayHasEvents = $events->contains(fn($event) => $event->start_at->toDateString() === $dateString);
                        @endphp

                        <div class="text-center text-xs p-1 rounded {{ $isToday ? 'bg-primary-500 text-white font-bold' : ($isCurrentMonth ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400') }} {{ $dayHasEvents ? 'font-semibold' : '' }}">
                            {{ $currentDay->day }}
                            @if($dayHasEvents)
                                <div class="w-1 h-1 bg-primary-500 rounded-full mx-auto mt-0.5"></div>
                            @endif
                        </div>

                        @php
                            $currentDay->addDay();
                        @endphp
                    @endwhile
                </div>
            </div>
        @endfor
    </div>
</div>
