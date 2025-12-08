@php
    $date = \Carbon\Carbon::parse($current_date);
    $startOfWeek = $date->copy()->startOfWeek();
    $endOfWeek = $date->copy()->endOfWeek();
    
    $events = $this->getEvents();
    $eventsByDate = $events->groupBy(fn($event) => $event->start_at->toDateString());
    
    $hours = range(0, 23);
@endphp

<div class="calendar-week-view overflow-x-auto">
    {{-- Day Headers --}}
    <div class="grid grid-cols-8 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
        <div class="p-2 border-r border-gray-200 dark:border-gray-700"></div>
        @for($i = 0; $i < 7; $i++)
            @php
                $day = $startOfWeek->copy()->addDays($i);
                $isToday = $day->isToday();
            @endphp
            <div class="p-2 text-center border-r border-gray-200 dark:border-gray-700">
                <div class="text-sm font-semibold {{ $isToday ? 'text-primary-600' : 'text-gray-700 dark:text-gray-300' }}">
                    {{ $day->format('D') }}
                </div>
                <div class="text-lg {{ $isToday ? 'bg-primary-500 text-white rounded-full w-8 h-8 flex items-center justify-center mx-auto' : 'text-gray-900 dark:text-gray-100' }}">
                    {{ $day->day }}
                </div>
            </div>
        @endfor
    </div>

    {{-- Time Grid --}}
    <div class="grid grid-cols-8">
        @foreach($hours as $hour)
            {{-- Hour Label --}}
            <div class="p-2 text-right text-xs text-gray-500 border-r border-b border-gray-200 dark:border-gray-700">
                {{ \Carbon\Carbon::createFromTime($hour)->format('g A') }}
            </div>

            {{-- Day Columns --}}
            @for($i = 0; $i < 7; $i++)
                @php
                    $day = $startOfWeek->copy()->addDays($i);
                    $dateString = $day->toDateString();
                    $hourEvents = $eventsByDate->get($dateString, collect())
                        ->filter(fn($event) => $event->start_at->hour === $hour);
                @endphp

                <div 
                    class="min-h-[60px] border-r border-b border-gray-200 dark:border-gray-700 p-1 relative"
                    wire:key="hour-{{ $dateString }}-{{ $hour }}"
                >
                    @foreach($hourEvents as $event)
                        <div 
                            class="text-xs p-1 rounded mb-1 cursor-pointer hover:opacity-80 transition"
                            style="background-color: {{ $event->type->getColor() }}20; border-left: 3px solid {{ $event->type->getColor() }}"
                        >
                            <div class="font-medium truncate">{{ $event->title }}</div>
                            <div class="text-gray-600 dark:text-gray-400">
                                {{ $event->start_at->format('g:i A') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endfor
        @endforeach
    </div>
</div>
