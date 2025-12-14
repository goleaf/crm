<div class="space-y-6">
    @if($ancestors->isNotEmpty())
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">
                {{ __('app.labels.ancestors') }}
            </h3>
            <div class="space-y-2">
                @foreach($ancestors as $ancestor)
                    <div class="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-gray-800 rounded">
                        <x-heroicon-o-arrow-up class="w-4 h-4 text-gray-500" />
                        <span>{{ $ancestor->name }}</span>
                        <span class="text-xs text-gray-500">(Level {{ $ancestor->level }})</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">
            {{ __('app.labels.current_group') }}
        </h3>
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded border-2 border-blue-200 dark:border-blue-700">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-shield-check class="w-5 h-5 text-blue-600" />
                <span class="font-medium">{{ $group->name }}</span>
                <span class="text-sm text-gray-600">(Level {{ $group->level }})</span>
            </div>
            @if($group->description)
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    {{ $group->description }}
                </p>
            @endif
        </div>
    </div>

    @if($descendants->isNotEmpty())
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">
                {{ __('app.labels.descendants') }}
            </h3>
            <div class="space-y-2">
                @foreach($descendants as $descendant)
                    <div class="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-gray-800 rounded" style="margin-left: {{ ($descendant->level - $group->level - 1) * 20 }}px;">
                        <x-heroicon-o-arrow-down class="w-4 h-4 text-gray-500" />
                        <span>{{ $descendant->name }}</span>
                        <span class="text-xs text-gray-500">(Level {{ $descendant->level }})</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>