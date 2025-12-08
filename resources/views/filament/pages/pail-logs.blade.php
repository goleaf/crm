<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">{{ __('app.pail.sections.overview') }}</x-slot>
            <x-slot name="description">{{ __('app.pail.descriptions.overview') }}</x-slot>

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white/80 p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                                {{ __('app.pail.cards.pcntl_title') }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $pcntlAvailable ? __('app.pail.cards.pcntl_ready') : __('app.pail.cards.pcntl_missing') }}
                            </p>
                        </div>

                        <x-filament::badge color="{{ $pcntlAvailable ? 'success' : 'danger' }}">
                            {{ $pcntlAvailable ? __('app.pail.status.enabled') : __('app.pail.status.disabled') }}
                        </x-filament::badge>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white/80 p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                            {{ __('app.pail.cards.dev_workflow') }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ __('app.pail.cards.dev_workflow_body') }}
                        </p>

                        <code class="mt-2 inline-flex rounded bg-gray-900 px-3 py-2 text-xs text-white dark:bg-gray-800">
                            php artisan pail --timeout=0
                        </code>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white/80 p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                            {{ __('app.pail.cards.driver_support') }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ __('app.pail.cards.driver_support_body') }}
                        </p>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('app.pail.sections.commands') }}</x-slot>
            <x-slot name="description">{{ __('app.pail.descriptions.commands') }}</x-slot>

            <div class="space-y-3">
                @foreach ($this->commands as $command)
                    <div class="rounded-lg border border-gray-200 bg-white/70 p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="space-y-1">
                                <p class="font-semibold text-gray-900 dark:text-gray-50">
                                    {{ $command['label'] }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ $command['description'] }}
                                </p>
                            </div>

                            <code class="inline-flex rounded bg-gray-900 px-3 py-2 text-xs text-white dark:bg-gray-800">
                                {{ $command['command'] }}
                            </code>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('app.pail.sections.filters') }}</x-slot>
            <x-slot name="description">{{ __('app.pail.descriptions.filters') }}</x-slot>

            <div class="grid gap-3 lg:grid-cols-2">
                @foreach ($this->filters as $filter)
                    <div class="rounded-lg border border-gray-200 bg-white/70 p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                                    {{ $filter['label'] }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ $filter['description'] }}
                                </p>
                            </div>

                            <code class="rounded bg-gray-900 px-3 py-2 text-xs text-white dark:bg-gray-800">
                                {{ $filter['example'] }}
                            </code>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('app.pail.sections.tips') }}</x-slot>
            <x-slot name="description">{{ __('app.pail.descriptions.tips') }}</x-slot>

            <ul class="list-disc space-y-2 pl-5 text-sm text-gray-700 dark:text-gray-200">
                @foreach ($this->tips as $tip)
                    <li>{{ $tip }}</li>
                @endforeach
            </ul>
        </x-filament::section>
    </div>
</x-filament-panels::page>
