@php
    $summary = $summary ?? [];
    $activities = $summary['activities_by_type'] ?? [];
@endphp

<div class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
            {{ __('app.labels.total_activities') }}
        </div>
        <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ $summary['total_activities'] ?? 0 }}
        </div>
    </div>

    @if(!empty($activities))
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
                {{ __('app.labels.activities_by_type') }}
            </div>
            <div class="space-y-2">
                @foreach($activities as $action => $count)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ ucfirst(str_replace('_', ' ', $action)) }}
                        </span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $count }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($summary['most_common_action'] ?? null)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('app.labels.most_common_action') }}
            </div>
            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ ucfirst(str_replace('_', ' ', $summary['most_common_action'])) }}
            </div>
        </div>
    @endif
</div>