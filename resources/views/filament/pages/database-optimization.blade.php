<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                    {{ __('app.sections.sqlite_optimization') }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ __('app.messages.sqlite_optimization_help') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-filament::button wire:click="publishOptimizationMigration" icon="heroicon-m-cloud-arrow-down">
                    {{ __('app.actions.publish_optimize_migration') }}
                </x-filament::button>
                <x-filament::button wire:click="refreshStatus" color="secondary" icon="heroicon-m-arrow-path">
                    {{ __('app.actions.refresh_optimization_status') }}
                </x-filament::button>
            </div>
        </div>

        @unless ($this->isSqlite())
            <x-filament::alert color="warning">
                {{ __('app.messages.sqlite_only_warning') }}
            </x-filament::alert>
        @endunless

        <x-filament::section>
            <x-slot name="heading">{{ __('app.sections.migration_status') }}</x-slot>
            <x-slot name="description">{{ __('app.messages.migration_status_help') }}</x-slot>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white/80 p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.labels.database_driver') }}</p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-gray-50">{{ $status['driver'] ?? '—' }}</p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white/80 p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.labels.sqlite_version') }}</p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-gray-50">
                        {{ $status['sqlite_version'] ?? '—' }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white/80 p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.labels.migration_applied') }}</p>
                    @php
                        $applied = $status['migration_applied'] ?? null;
                        $color = $applied === true ? 'success' : ($applied === false ? 'warning' : 'gray');
                        $label = $applied === true
                            ? __('app.labels.migration_applied')
                            : ($applied === false ? __('app.labels.migration_pending') : __('app.labels.not_available'));
                    @endphp
                    <x-filament::badge color="{{ $color }}">
                        {{ $label }}
                    </x-filament::badge>
                </div>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white/80 p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                        {{ __('app.labels.migration_path') }}
                    </p>
                    <p class="mt-2 text-sm text-gray-700 break-all dark:text-gray-200">
                        {{ $status['migration_path'] ?? __('app.labels.not_available') }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white/80 p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                        {{ __('app.labels.next_step') }}
                    </p>
                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-200">
                        @if (($status['migration_applied'] ?? null) === true)
                            {{ __('app.messages.optimization_migration_ran') }}
                        @elseif ($status['migration_path'] ?? false)
                            {{ __('app.messages.optimization_migration_pending') }}
                        @else
                            {{ __('app.messages.optimization_migration_missing') }}
                        @endif
                    </p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('app.sections.runtime_settings') }}</x-slot>
            <x-slot name="description">{{ __('app.messages.runtime_settings_help') }}</x-slot>

            <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-900/60">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('app.labels.setting') }}
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('app.labels.expected_value') }}
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('app.labels.actual_value') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white/70 dark:divide-gray-800 dark:bg-gray-900/60">
                        @forelse ($this->runtimePragmas as $pragma => $values)
                            @php
                                $matches = ($values['actual'] ?? null) === $values['expected'];
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap px-4 py-2 text-sm font-medium text-gray-900 dark:text-gray-50">
                                    {{ strtoupper(str_replace('_', ' ', $pragma)) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-700 dark:text-gray-200">
                                    {{ $values['expected'] }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2 text-sm">
                                    <x-filament::badge color="{{ $matches ? 'success' : 'warning' }}">
                                        {{ $values['actual'] ?? __('app.labels.not_available') }}
                                    </x-filament::badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ __('app.messages.runtime_settings_unavailable') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                {{ __('app.messages.sqlite_runtime_note') }}
            </p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
