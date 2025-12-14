<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('app.labels.acknowledgment_summary') }}
        </h3>
        <div class="text-sm text-gray-600">
            {{ $acknowledgments->count() }} / {{ $totalRecipients }} {{ __('app.labels.acknowledged') }}
        </div>
    </div>

    @if($message->require_acknowledgment)
        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500" />
                <span class="text-sm text-blue-700 dark:text-blue-300">
                    {{ __('app.messages.acknowledgment_required') }}
                </span>
            </div>
        </div>
    @endif

    @if($acknowledgments->isNotEmpty())
        <div>
            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">
                {{ __('app.labels.acknowledgments') }}
            </h4>
            <div class="space-y-3">
                @foreach($acknowledgments as $ack)
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $ack->user->name }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $ack->acknowledged_at->format('M j, g:i A') }}
                                </span>
                            </div>
                            @if($ack->response)
                                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $ack->response }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <x-heroicon-o-clock class="w-12 h-12 text-gray-400 mx-auto mb-3" />
            <div class="text-gray-500 dark:text-gray-400">
                {{ __('app.empty_states.no_acknowledgments_yet') }}
            </div>
        </div>
    @endif

    @if($acknowledgments->count() < $totalRecipients)
        <div>
            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">
                {{ __('app.labels.pending_acknowledgments') }}
            </h4>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $totalRecipients - $acknowledgments->count() }} {{ __('app.labels.recipients_pending') }}
            </div>
        </div>
    @endif
</div>