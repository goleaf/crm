<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                    {{ __('app.sections.insights_overview') }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ __('app.messages.insights_overview') }}
                </p>
            </div>

            <x-filament::button wire:click="refreshReport" icon="heroicon-m-arrow-path">
                {{ __('app.actions.refresh_insights') }}
            </x-filament::button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @forelse ($this->summaryCards as $card)
                <div class="rounded-xl border border-gray-200 bg-white/80 p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-50">
                                {{ $card['value'] !== null ? number_format($card['value'], 1) : __('app.labels.not_available') }}
                            </p>
                        </div>

                        <x-filament::badge color="{{ $card['color'] }}">
                            {{ $card['value'] !== null ? __('app.labels.score_badge', ['score' => number_format($card['value'], 1)]) : __('app.labels.not_available') }}
                        </x-filament::badge>
                    </div>

                    <div class="mt-4 h-2 rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-2 rounded-full {{ $card['barColor'] }}" style="width: {{ $card['progress'] }}%;"></div>
                    </div>
                </div>
            @empty
                <x-filament::placeholder icon="heroicon-o-chart-pie">
                    {{ __('app.messages.insights_no_summary') }}
                </x-filament::placeholder>
            @endforelse
        </div>

        <x-filament::section>
            <x-slot name="heading">{{ __('app.sections.insights_findings') }}</x-slot>
            <x-slot name="description">{{ __('app.messages.insights_findings_description') }}</x-slot>

            <div class="grid gap-6 lg:grid-cols-2">
                @foreach ($this->issueGroups as $group)
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                                {{ $group['label'] }}
                            </p>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('app.labels.issue_count', ['count' => count($group['items'])]) }}
                            </span>
                        </div>

                        @forelse ($group['items'] as $issue)
                            <div class="rounded-lg border border-gray-200 bg-white/70 p-4 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <p class="font-semibold text-gray-900 dark:text-gray-50">
                                            {{ $issue['title'] ?? __('app.labels.issue') }}
                                        </p>
                                        @if (! empty($issue['message']))
                                            <p class="text-gray-600 dark:text-gray-300">{{ $issue['message'] }}</p>
                                        @endif
                                    </div>

                                    @if (! empty($issue['insightClass']))
                                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            {{ $issue['insightClass'] }}
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $issue['file'] ?? __('app.labels.unknown_file') }}
                                    @if (! empty($issue['line']))
                                        · {{ __('app.labels.line_number', ['line' => $issue['line']]) }}
                                    @endif
                                </p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('app.messages.no_insights_found') }}
                            </p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
