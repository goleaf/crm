<x-filament-panels::page>
    <div class="space-y-6">
        {{-- PCOV Status --}}
        <x-filament::section>
            <x-slot name="heading">
                {{ __('app.labels.pcov_status') }}
            </x-slot>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="flex items-center gap-3">
                    @if($pcovEnabled)
                        <x-filament::icon
                            icon="heroicon-o-check-circle"
                            class="h-6 w-6 text-success-500"
                        />
                        <div>
                            <p class="text-sm font-medium">{{ __('app.labels.pcov_enabled') }}</p>
                            <p class="text-xs text-gray-500">{{ __('app.messages.pcov_enabled_description') }}</p>
                        </div>
                    @else
                        <x-filament::icon
                            icon="heroicon-o-x-circle"
                            class="h-6 w-6 text-danger-500"
                        />
                        <div>
                            <p class="text-sm font-medium">{{ __('app.labels.pcov_disabled') }}</p>
                            <p class="text-xs text-gray-500">{{ __('app.messages.pcov_disabled_description') }}</p>
                        </div>
                    @endif
                </div>

                @if($pcovEnabled && !empty($pcovConfig))
                    <div class="space-y-2">
                        <p class="text-sm font-medium">{{ __('app.labels.configuration') }}</p>
                        <dl class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">{{ __('app.labels.directory') }}:</dt>
                                <dd class="font-mono">{{ $pcovConfig['directory'] ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">{{ __('app.labels.exclude') }}:</dt>
                                <dd class="font-mono">{{ $pcovConfig['exclude'] ?? 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Coverage Statistics --}}
        <x-filament::section>
            <x-slot name="heading">
                {{ __('app.labels.coverage_statistics') }}
            </x-slot>

            @if($stats['overall'] > 0)
                <div class="grid gap-4 md:grid-cols-3">
                    {{-- Overall Coverage --}}
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('app.labels.overall_coverage') }}
                            </h3>
                            <x-filament::badge
                                :color="$stats['overall'] >= 80 ? 'success' : ($stats['overall'] >= 60 ? 'warning' : 'danger')"
                            >
                                {{ number_format($stats['overall'], 1) }}%
                            </x-filament::badge>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">
                            {{ $stats['covered_statements'] }} / {{ $stats['total_statements'] }} {{ __('app.labels.lines') }}
                        </p>
                        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div
                                class="h-full transition-all duration-300"
                                style="width: {{ $stats['overall'] }}%; background-color: {{ $stats['overall'] >= 80 ? 'rgb(34, 197, 94)' : ($stats['overall'] >= 60 ? 'rgb(251, 146, 60)' : 'rgb(239, 68, 68)') }}"
                            ></div>
                        </div>
                    </div>

                    {{-- Method Coverage --}}
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('app.labels.method_coverage') }}
                            </h3>
                            <x-filament::badge
                                :color="$stats['methods'] >= 80 ? 'success' : 'warning'"
                            >
                                {{ number_format($stats['methods'], 1) }}%
                            </x-filament::badge>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">
                            {{ $stats['covered_methods'] }} / {{ $stats['total_methods'] }} {{ __('app.labels.methods') }}
                        </p>
                        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div
                                class="h-full bg-primary-500 transition-all duration-300"
                                style="width: {{ $stats['methods'] }}%"
                            ></div>
                        </div>
                    </div>

                    {{-- Class Coverage --}}
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('app.labels.class_coverage') }}
                            </h3>
                            <x-filament::badge
                                :color="$stats['classes'] >= 80 ? 'success' : 'warning'"
                            >
                                {{ number_format($stats['classes'], 1) }}%
                            </x-filament::badge>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">
                            {{ $stats['covered_classes'] }} / {{ $stats['total_classes'] }} {{ __('app.labels.classes') }}
                        </p>
                        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div
                                class="h-full bg-primary-500 transition-all duration-300"
                                style="width: {{ $stats['classes'] }}%"
                            ></div>
                        </div>
                    </div>
                </div>

                @if($stats['generated_at'])
                    <p class="mt-4 text-xs text-gray-500">
                        {{ __('app.labels.last_generated') }}: {{ \Carbon\Carbon::createFromTimestamp($stats['generated_at'])->diffForHumans() }}
                    </p>
                @endif
            @else
                <div class="text-center py-8">
                    <x-filament::icon
                        icon="heroicon-o-chart-bar"
                        class="mx-auto h-12 w-12 text-gray-400"
                    />
                    <p class="mt-2 text-sm text-gray-500">{{ __('app.messages.no_coverage_data') }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ __('app.messages.run_coverage_to_generate') }}</p>
                </div>
            @endif
        </x-filament::section>

        {{-- Coverage by Category --}}
        @if(!empty($categoryStats))
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('app.labels.coverage_by_category') }}
                </x-slot>

                <div class="space-y-3">
                    @foreach($categoryStats as $category => $count)
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium">{{ $category }}</span>
                                <span class="text-gray-500">{{ $count }} {{ __('app.labels.covered_lines') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Quick Actions --}}
        <x-filament::section>
            <x-slot name="heading">
                {{ __('app.labels.quick_actions') }}
            </x-slot>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <h4 class="text-sm font-medium">{{ __('app.labels.run_coverage') }}</h4>
                    <p class="mt-1 text-xs text-gray-500">{{ __('app.messages.run_coverage_description') }}</p>
                    <div class="mt-3">
                        <code class="block rounded bg-gray-100 p-2 text-xs dark:bg-gray-800">
                            composer test:coverage
                        </code>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <h4 class="text-sm font-medium">{{ __('app.labels.view_html_report') }}</h4>
                    <p class="mt-1 text-xs text-gray-500">{{ __('app.messages.view_html_report_description') }}</p>
                    <div class="mt-3">
                        <code class="block rounded bg-gray-100 p-2 text-xs dark:bg-gray-800">
                            open coverage-html/index.html
                        </code>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
