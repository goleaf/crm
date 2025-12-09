<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('app.labels.total_links') }}
            </div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                {{ number_format($stats['total_links']) }}
            </div>
        </div>

        <div class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
            <div class="text-sm font-medium text-green-600 dark:text-green-400">
                {{ __('app.labels.active_links') }}
            </div>
            <div class="mt-1 text-2xl font-semibold text-green-900 dark:text-green-100">
                {{ number_format($stats['active_links']) }}
            </div>
        </div>

        <div class="rounded-lg bg-yellow-50 p-4 dark:bg-yellow-900/20">
            <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">
                {{ __('app.labels.expired_links') }}
            </div>
            <div class="mt-1 text-2xl font-semibold text-yellow-900 dark:text-yellow-100">
                {{ number_format($stats['expired_links']) }}
            </div>
        </div>

        <div class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
            <div class="text-sm font-medium text-red-600 dark:text-red-400">
                {{ __('app.labels.revoked_links') }}
            </div>
            <div class="mt-1 text-2xl font-semibold text-red-900 dark:text-red-100">
                {{ number_format($stats['revoked_links']) }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
            <div class="text-sm font-medium text-blue-600 dark:text-blue-400">
                {{ __('app.labels.total_clicks') }}
            </div>
            <div class="mt-1 text-2xl font-semibold text-blue-900 dark:text-blue-100">
                {{ number_format($stats['total_clicks']) }}
            </div>
        </div>

        <div class="rounded-lg bg-purple-50 p-4 dark:bg-purple-900/20">
            <div class="text-sm font-medium text-purple-600 dark:text-purple-400">
                {{ __('app.labels.average_clicks') }}
            </div>
            <div class="mt-1 text-2xl font-semibold text-purple-900 dark:text-purple-100">
                {{ $stats['average_clicks'] }}
            </div>
        </div>
    </div>
</div>
