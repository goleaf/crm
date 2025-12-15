<x-filament-panels::page>
    <x-filament::section>
        <div class="p-4 bg-gray-900 text-white rounded-lg font-mono text-sm overflow-x-auto whitespace-pre-wrap">
            {{ $output }}
        </div>

        <div class="mt-4">
            <x-filament::button wire:click="checkTranslations">
                Refresh Status
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>