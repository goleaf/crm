@php
    $date = \Carbon\Carbon::parse($current_date);
    $events = $this->getEvents();
    $hours = range(0, 23);
    $eventsByHour = $events->groupBy(fn($event) => $event->start_at->hour);
@endphp

<div class="calendar-day-view">
    {{-- Day Header --}}
    <div class="border-b border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
        <div class="text-center">
            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ $date->format('l') }}
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ $date->format('F j, Y') }}
            </div>
        </div>
    </div>

    {{-- Time Grid --}}
    <div class="overflow-y-auto max-h-[600px]">
        @foreach($hours as $hour)
            <div class="grid grid-cols-12 border-b border-gray-200 dark:border-gray-700 min-h-[80px]">
                {{-- Hour Label --}}
                <div class="col-span-2 p-2 text-right text-sm text-gray-500 border-r border-gray-200 dark:border-gray-700">
                    {{ \Carbon\Carbon::createFromTime($hour)->format('g:00 A') }}
                </div>

                {{-- Events Column --}}
                <div class="col-span-10 p-2 relative">
                    @php
                        $hourEvents = $eventsByHour->get($hour, collect());
                    @endphp

                    @foreach($hourEvents as $event)
                        <div 
                            class="p-2 rounded mb-2 cursor-pointer hover:opacity-80 transition"
                            style="background-color: {{ $event->type->getColor() }}20; border-left: 4px solid {{ $event->type->getColor() }}"
                        >
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $event->title }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $event->start_at->format('g:i A') }}
                                        @if($event->end_at)
                                            - {{ $event->end_at->format('g:i A') }}
                                        @endif
                                    </div>
                                    @if($event->location)
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            <x-heroicon-o-map-pin class="w-4 h-4 inline" />
                                            {{ $event->location }}
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                          style="background-color: {{ $event->status->getColor() }}20; color: {{ $event->status->getColor() }}">
                                        {{ $event->status->getLabel() }}
                                    </span>
                                </div>
                            </div>

                            @if($event->attendees && count($event->attendees) > 0)
                                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    <x-heroicon-o-user-group class="w-4 h-4 inline" />
                                    {{ count($event->attendees) }} {{ __('app.labels.attendees') }}
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @if($hourEvents->isEmpty())
                        <div class="h-full flex items-center justify-center text-gray-400 dark:text-gray-600">
                            {{-- Empty slot --}}
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
