<div class="space-y-6">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.subject') }}
            </label>
            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                {{ $message->subject }}
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.status') }}
            </label>
            <div class="mt-1">
                <x-filament::badge :color="$message->status_color">
                    {{ ucfirst($message->status) }}
                </x-filament::badge>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.priority') }}
            </label>
            <div class="mt-1">
                <x-filament::badge :color="$message->priority_color">
                    {{ ucfirst($message->priority) }}
                </x-filament::badge>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.sender') }}
            </label>
            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                {{ $message->sender->name }}
            </div>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ __('app.labels.message') }}
        </label>
        <div class="mt-1 p-3 bg-gray-50 dark:bg-gray-800 rounded text-sm text-gray-900 dark:text-gray-100">
            {{ $message->message }}
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.include_subgroups') }}
            </label>
            <div class="mt-1">
                @if($message->include_subgroups)
                    <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                @else
                    <x-heroicon-o-x-circle class="w-5 h-5 text-red-500" />
                @endif
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.require_acknowledgment') }}
            </label>
            <div class="mt-1">
                @if($message->require_acknowledgment)
                    <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                @else
                    <x-heroicon-o-x-circle class="w-5 h-5 text-red-500" />
                @endif
            </div>
        </div>
    </div>

    @if($message->scheduled_at)
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.scheduled_at') }}
            </label>
            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                {{ $message->scheduled_at->format('M j, Y g:i A') }}
            </div>
        </div>
    @endif

    @if($message->sent_at)
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.sent_at') }}
            </label>
            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                {{ $message->sent_at->format('M j, Y g:i A') }}
            </div>
        </div>
    @endif

    @if(!empty($deliveryStats))
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                {{ __('app.labels.delivery_statistics') }}
            </label>
            <div class="grid grid-cols-4 gap-4">
                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
                    <div class="text-2xl font-bold text-blue-600">{{ $deliveryStats['total_recipients'] ?? 0 }}</div>
                    <div class="text-xs text-gray-600">{{ __('app.labels.total_recipients') }}</div>
                </div>
                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded">
                    <div class="text-2xl font-bold text-green-600">{{ $deliveryStats['delivered'] ?? 0 }}</div>
                    <div class="text-xs text-gray-600">{{ __('app.labels.delivered') }}</div>
                </div>
                <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                    <div class="text-2xl font-bold text-yellow-600">{{ $deliveryStats['acknowledged'] ?? 0 }}</div>
                    <div class="text-xs text-gray-600">{{ __('app.labels.acknowledged') }}</div>
                </div>
                <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded">
                    <div class="text-2xl font-bold text-red-600">{{ $deliveryStats['failed'] ?? 0 }}</div>
                    <div class="text-xs text-gray-600">{{ __('app.labels.failed') }}</div>
                </div>
            </div>
            @if(isset($deliveryStats['acknowledgment_rate']))
                <div class="mt-3 text-center">
                    <span class="text-sm text-gray-600">
                        {{ __('app.labels.acknowledgment_rate') }}: {{ $deliveryStats['acknowledgment_rate'] }}%
                    </span>
                </div>
            @endif
        </div>
    @endif
</div>