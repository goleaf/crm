<section id="kanban" class="relative overflow-hidden bg-gradient-to-br from-primary-50/70 via-white to-white py-24 md:py-28 dark:from-gray-900 dark:via-gray-950 dark:to-black">
    <div class="absolute -top-24 right-12 h-56 w-56 rounded-full bg-primary/10 blur-3xl dark:bg-primary/25"></div>
    <div class="absolute -bottom-24 left-10 h-64 w-64 rounded-full bg-primary/5 blur-3xl dark:bg-primary/20"></div>

    <div class="container relative mx-auto max-w-6xl px-6 lg:px-8">
        <div class="grid items-start gap-12 lg:grid-cols-[1.05fr_0.95fr]">
            <div class="relative space-y-6">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-primary-700 shadow-sm backdrop-blur dark:bg-gray-900/80 dark:text-primary-200">
                    <span class="h-2 w-2 rounded-full bg-primary-500"></span>
                    Kanban Boards
                </span>
                <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl dark:text-white">Move deals forward with visual flow</h2>
                <p class="text-base leading-relaxed text-gray-600 dark:text-gray-300">
                    Build the exact pipeline your team needs: columns that mirror your sales stages, drag-and-drop motion for every deal,
                    and quick updates that keep forecasts current without leaving the board.
                </p>

                @php
                    $kanbanFeatures = [
                        ['title' => 'Visual pipeline management', 'description' => 'Color-coded columns, stage totals, and signals for risk and momentum.'],
                        ['title' => 'Drag-and-drop interface', 'description' => 'Reorder deals instantly with smooth drag targets and optimistic saves.'],
                        ['title' => 'Customizable columns/stages', 'description' => 'Stage sets, ordering, and colors adjust per workspace without code.'],
                        ['title' => 'Card-based deal management', 'description' => 'Cards surface amount, owner, contacts, and next steps right on the board.'],
                        ['title' => 'Quick status updates', 'description' => 'Inline changes to stage, probability, and close dates keep forecasts honest.'],
                        ['title' => 'Board customization', 'description' => 'Filter by owner, team, close window, or saved view to stay focused.'],
                        ['title' => 'Multiple board views', 'description' => 'Switch between board, list, and forecast snapshots without losing context.'],
                    ];
                @endphp

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($kanbanFeatures as $feature)
                        <div class="flex items-start gap-3 rounded-xl border border-gray-100 bg-white/80 p-4 shadow-sm backdrop-blur transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/80">
                            <span class="mt-1 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 text-primary-700 dark:bg-primary-900/60 dark:text-primary-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $feature['title'] }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $feature['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="relative">
                <div class="absolute inset-0 -z-10 translate-x-8 translate-y-8 rounded-3xl bg-primary/10 blur-3xl dark:bg-primary/25"></div>
                <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white/90 shadow-2xl shadow-primary/10 backdrop-blur dark:border-gray-800 dark:bg-gray-900/90">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7h18M3 12h18M3 17h18" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pipeline</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">Enterprise board</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-semibold">
                            <span class="rounded-full border border-primary-100 bg-primary-50 px-3 py-1 text-primary-700 dark:border-primary-900 dark:bg-primary-900/50 dark:text-primary-100">Board</span>
                            <span class="rounded-full border border-gray-200 px-3 py-1 text-gray-600 dark:border-gray-800 dark:text-gray-300">List</span>
                            <span class="rounded-full border border-gray-200 px-3 py-1 text-gray-600 dark:border-gray-800 dark:text-gray-300">Forecast</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3 text-xs text-gray-600 dark:border-gray-800 dark:text-gray-300">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.5V18a3 3 0 00-3-3H7.5a3 3 0 00-3 3v1.5M12 11.25a3 3 0 100-6 3 3 0 000 6z" />
                                </svg>
                                Owner: West Coast
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l2.5 2.5M12 21a9 9 0 100-18 9 9 0 000 18z" />
                                </svg>
                                Close in 30 days
                            </span>
                        </div>
                        <span class="inline-flex items-center gap-1 text-primary-700 dark:text-primary-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 17.25l-3.5 2.1 1-4.05-3.13-2.85 4.13-.35L12 8.5l1.5 3.6 4.13.35-3.13 2.85 1 4.05z" />
                            </svg>
                            Saved view: Focus
                        </span>
                    </div>

                    @php
                        $kanbanColumns = [
                            [
                                'title' => 'Qualify',
                                'count' => '03',
                                'dot' => 'bg-primary-500',
                                'cards' => [
                                    [
                                        'title' => 'Acme revamp',
                                        'meta' => 'Owner: Avery · Warm lead',
                                        'amount' => '$56,400',
                                        'badge' => 'Design',
                                        'badgeColor' => 'bg-primary-50 text-primary-700 dark:bg-primary-900/60 dark:text-primary-100',
                                        'status' => 'Probability 45%',
                                        'statusDot' => 'bg-primary-500',
                                        'timeline' => 'Updated 1h ago',
                                    ],
                                    [
                                        'title' => 'Northwind rollout',
                                        'meta' => 'Owner: Casey · 2 contacts',
                                        'amount' => '$31,200',
                                        'badge' => 'Pilot',
                                        'badgeColor' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-100',
                                        'status' => 'Due diligence',
                                        'statusDot' => 'bg-emerald-500',
                                        'timeline' => 'New note today',
                                    ],
                                ],
                            ],
                            [
                                'title' => 'Proposal',
                                'count' => '02',
                                'dot' => 'bg-amber-500',
                                'cards' => [
                                    [
                                        'title' => 'Vertex renewal',
                                        'meta' => 'Owner: Drew · Legal in flight',
                                        'amount' => '$84,900',
                                        'badge' => 'Renewal',
                                        'badgeColor' => 'bg-amber-50 text-amber-800 dark:bg-amber-900/50 dark:text-amber-100',
                                        'status' => 'Probability 65%',
                                        'statusDot' => 'bg-amber-500',
                                        'timeline' => 'Close date: 18 days',
                                    ],
                                    [
                                        'title' => 'Lighthouse upgrade',
                                        'meta' => 'Owner: Riley · CFO looped in',
                                        'amount' => '$47,300',
                                        'badge' => 'Expansion',
                                        'badgeColor' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-100',
                                        'status' => 'Docs in review',
                                        'statusDot' => 'bg-blue-500',
                                        'timeline' => 'Edited 3h ago',
                                    ],
                                ],
                            ],
                            [
                                'title' => 'Negotiation',
                                'count' => '02',
                                'dot' => 'bg-emerald-500',
                                'cards' => [
                                    [
                                        'title' => 'Polar analytics',
                                        'meta' => 'Owner: Morgan · Exec sponsor set',
                                        'amount' => '$129,000',
                                        'badge' => 'Enterprise',
                                        'badgeColor' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-100',
                                        'status' => 'Probability 78%',
                                        'statusDot' => 'bg-emerald-500',
                                        'timeline' => 'Next step tomorrow',
                                    ],
                                    [
                                        'title' => 'Harbor supply',
                                        'meta' => 'Owner: Taylor · Redlines out',
                                        'amount' => '$63,750',
                                        'badge' => 'Logistics',
                                        'badgeColor' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-100',
                                        'status' => 'Awaiting signoff',
                                        'statusDot' => 'bg-indigo-500',
                                        'timeline' => 'Updated just now',
                                    ],
                                ],
                            ],
                        ];
                    @endphp

                    <div class="grid grid-cols-1 gap-3 px-4 py-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($kanbanColumns as $column)
                            <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-3 shadow-inner dark:border-gray-800 dark:bg-gray-900/70">
                                <div class="mb-3 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full {{ $column['dot'] }}"></span>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $column['title'] }}</p>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $column['count'] }}</span>
                                </div>
                                <div class="space-y-2.5">
                                    @foreach ($column['cards'] as $card)
                                        <div class="group rounded-lg border border-white/60 bg-white/90 px-3 py-3 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-primary-100 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/80">
                                            <div class="flex items-start justify-between">
                                                <div class="space-y-1">
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $card['title'] }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $card['meta'] }}</p>
                                                </div>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 transition-colors duration-200 group-hover:text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 6h6M9 12h6M9 18h6" />
                                                </svg>
                                            </div>
                                            <div class="mt-3 flex items-center justify-between">
                                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $card['amount'] }}</span>
                                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $card['badgeColor'] }}">{{ $card['badge'] }}</span>
                                            </div>
                                            <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                    <span class="h-1.5 w-1.5 rounded-full {{ $card['statusDot'] }}"></span>
                                                    {{ $card['status'] }}
                                                </span>
                                                <span class="inline-flex items-center gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l2.5 2.5M12 21a9 9 0 100-18 9 9 0 000 18z" />
                                                    </svg>
                                                    {{ $card['timeline'] }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
