<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium">{{ __('app.labels.permissions_matrix_for', ['role' => $role->display_name ?? $role->name]) }}</h3>
        <div class="text-sm text-gray-500">
            {{ __('app.labels.total_permissions', ['count' => count($role->permissions)]) }}
        </div>
    </div>

    @if($role->parentRole)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <p class="text-sm text-blue-800">
                <strong>{{ __('app.labels.inherits_from') }}:</strong> {{ $role->parentRole->display_name ?? $role->parentRole->name }}
            </p>
        </div>
    @endif

    @if(empty($matrix))
        <div class="text-center py-8 text-gray-500">
            {{ __('app.messages.no_permissions_assigned') }}
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('app.labels.resource') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('app.labels.permissions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($matrix as $resource => $actions)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ str_replace(['_', '-'], ' ', title_case($resource)) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($actions as $action => $granted)
                                        @if($granted)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ str_replace('_', ' ', title_case($action)) }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($role->childRoles->isNotEmpty())
        <div class="mt-6">
            <h4 class="text-md font-medium mb-3">{{ __('app.labels.child_roles') }}</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($role->childRoles as $childRole)
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <div class="font-medium">{{ $childRole->display_name ?? $childRole->name }}</div>
                        <div class="text-sm text-gray-500">
                            {{ __('app.labels.permissions_count', ['count' => $childRole->permissions->count()]) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>