<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold">{{ __('app.wirelive.heading') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('app.wirelive.subheading') }}
                </p>
            </div>
            <a
                class="inline-flex items-center gap-1 text-primary-600 dark:text-primary-400 text-sm font-medium"
                href="https://www.youtube.com/playlist?list=PLH3DZfpF7H73EXPI_AhwUBud22VufndZV"
                target="_blank"
                rel="noreferrer"
            >
                {{ __('app.wirelive.watch_playlist') }}
                <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
            </a>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($this->getPlaylist() as $talk)
                <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ $talk['speaker'] }}
                    </div>
                    <div class="mt-1 font-semibold text-sm text-gray-900 dark:text-gray-100">
                        {{ $talk['title'] }}
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <a
                            class="inline-flex items-center gap-1 text-primary-600 dark:text-primary-400 text-sm font-medium"
                            href="{{ $talk['url'] }}"
                            target="_blank"
                            rel="noreferrer"
                        >
                            {{ __('app.wirelive.watch_talk') }}
                            <x-heroicon-o-play-circle class="w-4 h-4" />
                        </a>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('app.wirelive.livewire_focus') }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
