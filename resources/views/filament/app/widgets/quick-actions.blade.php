<x-filament-widgets::widget>
    <x-filament::section
        heading="Quick actions"
        description="Jump into the core CRM workflows."
    >
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($actions as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="group flex items-start justify-between rounded-xl border border-gray-200/80 bg-white/80 p-4 shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-md"
                >
                    <div>
                        <div class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                            <x-dynamic-component :component="$action['icon']" class="h-4 w-4 text-primary-500" />
                            <span>{{ $action['label'] }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">{{ $action['description'] }}</p>
                    </div>
                    <x-heroicon-o-arrow-top-right-on-square class="h-5 w-5 text-gray-300 group-hover:text-primary-500" />
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
