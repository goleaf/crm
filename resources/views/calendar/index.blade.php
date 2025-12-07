@php
    use App\Enums\CalendarEventType;
@endphp

<x-layout.app-shell
    title="Calendar"
    description="Schedule meetings, demos, and follow-ups without leaving the CRM."
    :breadcrumbs="[['label' => 'Workspace', 'href' => url('/dashboard')], ['label' => 'Calendar']]">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Upcoming</p>
                    <h2 class="text-xl font-semibold text-gray-900">Scheduled events</h2>
                </div>
            </div>

            <div class="space-y-3">
                @forelse($events as $event)
                    <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
                        <div class="text-center">
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $event->start_at->format('M d') }}</p>
                            <p class="text-xs text-gray-500">{{ $event->start_at->format('H:i') }}</p>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $event->title }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $event->type->getLabel() }} ·
                                @if($event->is_all_day)
                                    All day
                                @else
                                    {{ $event->start_at->format('H:i') }} @if($event->end_at)– {{ $event->end_at->format('H:i') }} @endif
                                @endif
                                @if($event->location)
                                    · {{ $event->location }}
                                @endif
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-semibold bg-primary/10 text-primary">
                            {{ $event->status->getLabel() }}
                        </span>
                    </div>
                @empty
                    <div class="p-4 rounded-xl border border-dashed border-gray-200 text-sm text-gray-600 bg-white dark:bg-gray-900">
                        No events yet. Schedule your first meeting to see it here.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="space-y-4">
            <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Schedule</p>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">New event</h3>
                    </div>
                </div>

                <form method="POST" action="{{ route('calendar.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-700">Title</label>
                        <input name="title" required maxlength="255" class="mt-1 w-full rounded-md border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700">Type</label>
                        <select name="type" class="mt-1 w-full rounded-md border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm">
                            @foreach($eventTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Start</label>
                            <input type="datetime-local" name="start_at" required class="mt-1 w-full rounded-md border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">End</label>
                            <input type="datetime-local" name="end_at" class="mt-1 w-full rounded-md border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700">Location</label>
                        <input name="location" class="mt-1 w-full rounded-md border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700">Meeting URL</label>
                        <input name="meeting_url" class="mt-1 w-full rounded-md border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Attendee name</label>
                            <input name="attendees[0][name]" class="mt-1 w-full rounded-md border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm" placeholder="Alex Doe" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Attendee email</label>
                            <input name="attendees[0][email]" class="mt-1 w-full rounded-md border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm" placeholder="alex@example.com" />
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md bg-primary text-white text-sm font-semibold hover:bg-primary-600 transition">
                            <x-heroicon-o-plus class="w-4 h-4"/> Schedule event
                        </button>
                    </div>

                    @if(session('status'))
                        <p class="text-xs text-green-600">{{ session('status') }}</p>
                    @endif
                    @if($errors->any())
                        <ul class="text-xs text-red-600 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </form>
            </div>

            <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 space-y-3">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-link class="w-5 h-5 text-primary"/>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Calendar sync</p>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Two-way sync with Google and Outlook is in progress. You can schedule events now and connect sync once released.
                </p>
            </div>
        </div>
    </div>
</x-layout.app-shell>
