<x-filament-panels::page>
    <div class="space-y-4">
        {{-- View Controls --}}
        <div class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
            {{-- Navigation Controls --}}
            <div class="flex items-center gap-2">
                <x-filament::button
                    wire:click="previousPeriod"
                    size="sm"
                    color="gray"
                    icon="heroicon-o-chevron-left"
                >
                </x-filament::button>

                <x-filament::button
                    wire:click="today"
                    size="sm"
                    color="gray"
                >
                    {{ __('app.labels.today') }}
                </x-filament::button>

                <x-filament::button
                    wire:click="nextPeriod"
                    size="sm"
                    color="gray"
                    icon="heroicon-o-chevron-right"
                    icon-position="after"
                >
                </x-filament::button>

                <div class="ml-4 text-lg font-semibold">
                    @php
                        $date = \Carbon\Carbon::parse($current_date);
                    @endphp
                    @if($view_mode === 'day')
                        {{ $date->format('F j, Y') }}
                    @elseif($view_mode === 'week')
                        {{ $date->startOfWeek()->format('M j') }} - {{ $date->endOfWeek()->format('M j, Y') }}
                    @elseif($view_mode === 'month')
                        {{ $date->format('F Y') }}
                    @else
                        {{ $date->format('Y') }}
                    @endif
                </div>
            </div>

            {{-- View Mode Selector --}}
            <div class="flex items-center gap-2">
                <x-filament::button
                    wire:click="changeView('day')"
                    size="sm"
                    :color="$view_mode === 'day' ? 'primary' : 'gray'"
                >
                    {{ __('app.labels.day') }}
                </x-filament::button>

                <x-filament::button
                    wire:click="changeView('week')"
                    size="sm"
                    :color="$view_mode === 'week' ? 'primary' : 'gray'"
                >
                    {{ __('app.labels.week') }}
                </x-filament::button>

                <x-filament::button
                    wire:click="changeView('month')"
                    size="sm"
                    :color="$view_mode === 'month' ? 'primary' : 'gray'"
                >
                    {{ __('app.labels.month') }}
                </x-filament::button>

                <x-filament::button
                    wire:click="changeView('year')"
                    size="sm"
                    :color="$view_mode === 'year' ? 'primary' : 'gray'"
                >
                    {{ __('app.labels.year') }}
                </x-filament::button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.live.debounce.500ms="filters.search"
                        placeholder="{{ __('app.placeholders.search_events') }}"
                    />
                </x-filament::input.wrapper>
            </div>

            <div>
                <select
                    wire:model.live="filters.types"
                    multiple
                    class="fi-input block w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    @foreach(\App\Enums\CalendarEventType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->getLabel() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select
                    wire:model.live="filters.statuses"
                    multiple
                    class="fi-input block w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    @foreach(\App\Enums\CalendarEventStatus::cases() as $status)
                        <option value="{{ $status->value }}">{{ $status->getLabel() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select
                    wire:model.live="filters.team_members"
                    multiple
                    class="fi-input block w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    @php
                        $teamMembers = $this->getTeamMembers();
                    @endphp
                    @foreach($teamMembers as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Zap-powered availability --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 space-y-3">
            @php
                $nextSlot = $this->getNextBookableSlot();
            @endphp

            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('app.labels.bookable_slots') }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('app.messages.zap_managed_slots') }}</p>
                </div>
                @if($nextSlot)
                    <div class="text-right">
                        <p class="text-xs font-semibold text-gray-500">{{ __('app.labels.next_available_slot') }}</p>
                        <p class="text-sm font-semibold text-primary-600 dark:text-primary-400">
                            {{ \Illuminate\Support\Carbon::parse($nextSlot['date'])->format('M j') }}
                            · {{ $nextSlot['start_time'] }} – {{ $nextSlot['end_time'] }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @forelse($this->getBookableSlots() as $slot)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $slot['start_time'] }} – {{ $slot['end_time'] }}
                            </p>
                            <p class="text-xs text-gray-500">{{ \Illuminate\Support\Carbon::parse($current_date)->format('D, M j') }}</p>
                        </div>
                        <span @class([
                            'text-xs font-semibold px-2 py-1 rounded-full',
                            'bg-green-100 text-green-700' => $slot['is_available'] ?? false,
                            'bg-amber-100 text-amber-700' => ! ($slot['is_available'] ?? false),
                        ])>
                            {{ $slot['is_available'] ? __('app.labels.available') : __('app.labels.unavailable') }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-300">
                        {{ __('app.messages.no_bookable_slots') }}
                    </p>
                @endforelse
            </div>
        </div>

        {{-- Team Events Toggle --}}
        <div class="flex items-center gap-2">
            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    wire:model.live="filters.show_team_events"
                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    {{ __('app.labels.show_team_events') }}
                </span>
            </label>
        </div>

        {{-- Calendar Grid --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            @if($view_mode === 'month')
                @include('filament.pages.calendar.month-view')
            @elseif($view_mode === 'week')
                @include('filament.pages.calendar.week-view')
            @elseif($view_mode === 'day')
                @include('filament.pages.calendar.day-view')
            @else
                @include('filament.pages.calendar.year-view')
            @endif
        </div>
    </div>

    @script
    <script>
        $wire.on('event-created', () => {
            $wire.$refresh();
        });

        $wire.on('event-updated', () => {
            $wire.$refresh();
        });
    </script>
    @endscript
</x-filament-panels::page>
