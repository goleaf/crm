<x-filament-panels::page>
    <x-filament-panels::form wire:submit="testFilter">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit">
                Test Filter
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>