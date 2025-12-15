<section class="py-24 md:py-28 bg-white dark:bg-gray-950 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-indigo-50/40 dark:from-primary/10 dark:to-gray-900 pointer-events-none"></div>

    <div class="relative max-w-6xl mx-auto px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
        <div class="space-y-6">
            <div class="inline-flex items-center px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold uppercase tracking-wide">
                Calendar · Coming Soon
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold text-black dark:text-white leading-tight">
                A calendar that stays in lockstep with your CRM
            </h2>
            <p class="text-gray-600 dark:text-gray-300 max-w-2xl leading-relaxed">
                Plan meetings, manage follow-ups, and keep your pipeline and support work synchronized without jumping tools.
            </p>
            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ auth()->check() ? route('calendar') : route('register') }}"
                   class="inline-flex items-center gap-2 px-5 py-3 rounded-md bg-primary text-white font-medium shadow-sm hover:bg-primary-600 transition">
                    @if(auth()->check())
                        Open calendar
                    @else
                        Get notified
                    @endif
                    <x-heroicon-o-bell-alert class="w-4 h-4"/>
                </a>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    @if(auth()->check())
                        Schedule events inside your workspace
                    @else
                        Join the early access list
                    @endif
                </span>
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                    <x-heroicon-o-calendar class="w-4 h-4"/> Calendar view
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-200">
                    <x-heroicon-o-clock class="w-4 h-4"/> Event scheduling
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200">
                    <x-heroicon-o-user-group class="w-4 h-4"/> Meeting management
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-200">
                    <x-heroicon-o-link class="w-4 h-4"/> Calendar sync
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3">
                <div class="flex items-start gap-3 p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-white/70 dark:bg-gray-900/60">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <x-heroicon-o-link class="w-5 h-5"/>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Two-way sync</p>
                        <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">Google & Outlook mirror invites, updates, and cancellations in real time.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-white/70 dark:bg-gray-900/60">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200">
                        <x-heroicon-o-user-group class="w-5 h-5"/>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Smart handoffs</p>
                        <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">Auto-create notes and follow-up tasks when meetings end—no extra clicks.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/40">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-calendar class="w-5 h-5 text-primary"/>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Calendar view</p>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        Day, week, and month layouts tuned for CRM tasks, deals, and reminders.
                    </p>
                </div>
                <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/40">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-primary"/>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Event scheduling</p>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        Book meetings, set reminders, and share availability from the same workspace.
                    </p>
                </div>
                <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/40">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-user-group class="w-5 h-5 text-primary"/>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Meeting management</p>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        Track attendees, agendas, notes, and follow-up tasks with automatic updates.
                    </p>
                </div>
                <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/40">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-link class="w-5 h-5 text-primary"/>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Calendar sync</p>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        Connect Google or Outlook to keep invites and reminders in perfect sync.
                    </p>
                </div>
            </div>
        </div>

        <div class="relative">
            <div class="absolute -inset-8 rounded-3xl bg-gradient-to-tr from-primary/10 via-white to-indigo-50 blur-2xl opacity-70 dark:from-primary/20 dark:via-gray-900 dark:to-gray-900"></div>
            <div class="relative bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl shadow-xl p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="text-left">
                        <p class="text-xs uppercase tracking-wide text-gray-400">Week view</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">April 7 — 13</p>
                    </div>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-primary bg-primary/10 px-3 py-1 rounded-full">
                        <x-heroicon-o-sparkles class="w-4 h-4"/> Smart scheduling
                    </span>
                </div>
                <div class="grid grid-cols-7 gap-2 text-center text-xs font-semibold text-gray-500">
                    @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                        <div class="py-2 rounded-lg border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60">{{ $day }}</div>
                    @endforeach
                </div>
                <div class="space-y-3">
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-800 bg-primary/5 dark:bg-primary/10">
                        <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-primary text-white flex items-center justify-center text-sm font-semibold">09:00</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Pipeline Review</p>
                            <p class="text-xs text-gray-500">Deals + tasks · 30 min</p>
                        </div>
                        <span class="ml-auto text-[11px] font-semibold text-primary bg-primary/10 px-2 py-1 rounded-full">Calendar view</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-800 bg-green-50 dark:bg-green-900/20">
                        <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-green-500 text-white flex items-center justify-center text-sm font-semibold">12:30</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Customer Demo</p>
                            <p class="text-xs text-gray-500">Link to Opportunity · Meeting</p>
                        </div>
                        <span class="ml-auto text-[11px] font-semibold text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded-full">Event scheduling</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-800 bg-indigo-50 dark:bg-indigo-900/20">
                        <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-indigo-500 text-white flex items-center justify-center text-sm font-semibold">15:00</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Support Sync</p>
                            <p class="text-xs text-gray-500">Linked to Case · 45 min</p>
                        </div>
                        <span class="ml-auto text-[11px] font-semibold text-indigo-700 dark:text-indigo-200 bg-indigo-100 dark:bg-indigo-900/30 px-2 py-1 rounded-full">Meeting management</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-800 bg-orange-50 dark:bg-orange-900/20">
                        <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-orange-500 text-white flex items-center justify-center text-sm font-semibold">All day</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Two-way sync</p>
                            <p class="text-xs text-gray-500">Google & Outlook · Status in sync</p>
                        </div>
                        <span class="ml-auto text-[11px] font-semibold text-orange-700 dark:text-orange-200 bg-orange-100 dark:bg-orange-900/30 px-2 py-1 rounded-full">Calendar sync</span>
                    </div>
                </div>
                <div class="pt-2 border-t border-dashed border-gray-200 dark:border-gray-800 flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-bolt class="w-4 h-4 text-primary"/>
                        Auto-create follow-up tasks after meetings
                    </div>
                    <span class="text-xs text-primary font-semibold">In beta</span>
                </div>
            </div>
        </div>
    </div>
</section>
