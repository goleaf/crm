@php
    $stats = $stats ?? [];
@endphp

<div class="grid grid-cols-2 gap-4">
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
            {{ __('app.labels.total_logins') }}
        </div>
        <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ $stats['total_logins'] ?? 0 }}
        </div>
    </div>

    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
            {{ __('app.labels.successful_logins') }}
        </div>
        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
            {{ $stats['successful_logins'] ?? 0 }}
        </div>
    </div>

    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
            {{ __('app.labels.failed_logins') }}
        </div>
        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
            {{ $stats['failed_logins'] ?? 0 }}
        </div>
    </div>

    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
            {{ __('app.labels.success_rate') }}
        </div>
        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
            {{ ($stats['success_rate'] ?? 0) }}%
        </div>
    </div>
</div>