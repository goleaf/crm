<div class="space-y-4">
    <div class="text-sm text-gray-600 dark:text-gray-400">
        {{ __('app.helpers.security_group_hierarchy_view') }}
    </div>

    @if($groups->isEmpty())
        <div class="text-center py-8">
            <div class="text-gray-500 dark:text-gray-400">
                {{ __('app.empty_states.no_security_groups') }}
            </div>
        </div>
    @else
        <div class="space-y-2">
            @foreach($groups->where('level', 0) as $rootGroup)
                @include('filament.partials.security-group-tree-node', ['group' => $rootGroup, 'level' => 0])
            @endforeach
        </div>
    @endif
</div>