<div class="space-y-6">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.action') }}
            </label>
            <div class="mt-1">
                <x-filament::badge :color="match($auditLog->action) {
                    'created' => 'success',
                    'updated' => 'warning', 
                    'deleted' => 'danger',
                    'member_added' => 'info',
                    'member_removed' => 'gray',
                    'record_access_granted' => 'success',
                    'record_access_revoked' => 'danger',
                    'broadcast_sent' => 'primary',
                    default => 'gray'
                }">
                    {{ $auditLog->action_description }}
                </x-filament::badge>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.user') }}
            </label>
            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                {{ $auditLog->user?->name ?? __('app.labels.system') }}
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.timestamp') }}
            </label>
            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                {{ $auditLog->created_at->format('M j, Y g:i A') }}
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.ip_address') }}
            </label>
            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                {{ $auditLog->ip_address ?? __('app.labels.unknown') }}
            </div>
        </div>
    </div>

    @if($auditLog->entity_type)
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.entity') }}
            </label>
            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                {{ class_basename($auditLog->entity_type) }} #{{ $auditLog->entity_id }}
            </div>
        </div>
    @endif

    @if(!empty($changes))
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                {{ __('app.labels.changes') }}
            </label>
            <div class="space-y-3">
                @foreach($changes as $field => $change)
                    <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded">
                        <div class="font-medium text-sm text-gray-900 dark:text-gray-100 mb-2">
                            {{ ucfirst(str_replace('_', ' ', $field)) }}
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-red-600 dark:text-red-400 font-medium">{{ __('app.labels.old') }}:</span>
                                <div class="mt-1 p-2 bg-red-50 dark:bg-red-900/20 rounded">
                                    {{ is_array($change['old']) ? json_encode($change['old'], JSON_PRETTY_PRINT) : ($change['old'] ?? __('app.labels.empty')) }}
                                </div>
                            </div>
                            <div>
                                <span class="text-green-600 dark:text-green-400 font-medium">{{ __('app.labels.new') }}:</span>
                                <div class="mt-1 p-2 bg-green-50 dark:bg-green-900/20 rounded">
                                    {{ is_array($change['new']) ? json_encode($change['new'], JSON_PRETTY_PRINT) : ($change['new'] ?? __('app.labels.empty')) }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($auditLog->metadata)
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                {{ __('app.labels.metadata') }}
            </label>
            <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded">
                <pre class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ json_encode($auditLog->metadata, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    @endif

    @if($auditLog->notes)
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.notes') }}
            </label>
            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                {{ $auditLog->notes }}
            </div>
        </div>
    @endif
</div>