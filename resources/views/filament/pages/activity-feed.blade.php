<x-filament-panels::page>
    <div class="space-y-6">
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex items-center gap-x-3 overflow-hidden px-6 py-4">
                <div class="grid flex-1 gap-y-1">
                    <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        {{ __('app.pages.activity_feed.description') }}
                    </h3>
                </div>
            </div>

            <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
