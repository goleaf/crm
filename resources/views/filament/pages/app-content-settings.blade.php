<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
            @php
                $lastUpdated = $this->lastUpdatedAt(
                    timezone: config('app.timezone', 'UTC'),
                    format: 'F j, Y, g:i a',
                );
            @endphp

            <div class="text-sm text-gray-600 dark:text-gray-300">
                <span class="font-semibold">{{ __('db-config::db-config.last_updated') }}:</span>
                <span>{{ $lastUpdated ? $lastUpdated . ' ' . config('app.timezone', 'UTC') : __('app.labels.never') }}</span>
            </div>

            <x-filament::button type="submit">
                {{ __('db-config::db-config.save') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
