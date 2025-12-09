<div class="relative" x-data="{ open: false }">
    @php($activeLocale = $availableLocales[$currentLocale] ?? ['flag' => '🌐', 'name' => strtoupper($currentLocale)])

    <button
        @click="open = !open"
        @click.away="open = false"
        type="button"
        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
    >
        <span class="text-lg">{{ $activeLocale['flag'] }}</span>
        <span>{{ $activeLocale['name'] }}</span>
        <svg class="h-4 w-4 transition" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-gray-700"
        style="display: none;"
    >
        <div class="py-1">
            @foreach ($availableLocales as $locale => $data)
                <button
                    wire:click="switchLanguage('{{ $locale }}')"
                    @click="open = false"
                    class="flex w-full items-center gap-3 px-4 py-2 text-sm transition hover:bg-gray-100 dark:hover:bg-gray-700 {{ $currentLocale === $locale ? 'bg-gray-50 font-semibold text-primary-600 dark:bg-gray-700 dark:text-primary-400' : 'text-gray-700 dark:text-gray-200' }}"
                >
                    <span class="text-lg">{{ $data['flag'] }}</span>
                    <span>{{ $data['name'] }}</span>
                    @if ($currentLocale === $locale)
                        <svg class="ml-auto h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
</div>
