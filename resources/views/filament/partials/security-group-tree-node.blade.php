<div class="flex items-center space-x-2 py-2" style="margin-left: {{ $level * 20 }}px;">
    <div class="flex items-center space-x-2">
        @if($group->children->isNotEmpty())
            <x-heroicon-o-folder class="w-4 h-4 text-gray-500" />
        @else
            <x-heroicon-o-document class="w-4 h-4 text-gray-400" />
        @endif
        
        <span class="font-medium text-gray-900 dark:text-gray-100">
            {{ $group->name }}
        </span>
        
        @if($group->is_primary_group)
            <x-filament::badge color="primary" size="xs">
                {{ __('app.labels.primary') }}
            </x-filament::badge>
        @endif
        
        @if(!$group->active)
            <x-filament::badge color="gray" size="xs">
                {{ __('app.labels.inactive') }}
            </x-filament::badge>
        @endif
        
        <span class="text-xs text-gray-500">
            ({{ $group->members->count() }} {{ __('app.labels.members') }})
        </span>
    </div>
</div>

@if($group->children->isNotEmpty())
    @foreach($group->children as $child)
        @include('filament.partials.security-group-tree-node', ['group' => $child, 'level' => $level + 1])
    @endforeach
@endif