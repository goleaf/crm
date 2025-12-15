<x-filament-widgets::widget>
    <x-filament::section>
        @if($summary === null)
            <div class="text-center py-8">
                <p class="text-gray-500">{{ __('app.messages.no_project_selected') }}</p>
            </div>
        @else
            <div class="space-y-6">
                {{-- Schedule Summary --}}
                <div>
                    <h3 class="text-lg font-semibold mb-4">{{ __('app.labels.schedule_summary') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.labels.total_tasks') }}</div>
                            <div class="text-2xl font-bold mt-1">{{ $summary['total_tasks'] }}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $summary['completed_tasks'] }} {{ __('app.labels.completed') }},
                                {{ $summary['in_progress_tasks'] }} {{ __('app.labels.in_progress') }}
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.labels.critical_path') }}</div>
                            <div class="text-2xl font-bold mt-1">{{ $summary['critical_path_length'] }} {{ __('app.labels.days') }}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $summary['critical_tasks_count'] }} {{ __('app.labels.critical_tasks') }}
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.labels.schedule_status') }}</div>
                            <div class="text-2xl font-bold mt-1">
                                @if($summary['on_schedule'])
                                    <span class="text-green-600">{{ __('app.labels.on_track') }}</span>
                                @else
                                    <span class="text-red-600">{{ __('app.labels.at_risk') }}</span>
                                @endif
                            </div>
                            @if($summary['blocked_tasks'] > 0)
                                <div class="text-xs text-orange-600 mt-1">
                                    {{ $summary['blocked_tasks'] }} {{ __('app.labels.blocked_tasks') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Critical Path Tasks --}}
                @if($criticalPath->isNotEmpty())
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ __('app.labels.critical_path_tasks') }}</h3>
                        <div class="space-y-2">
                            @foreach($criticalPath as $task)
                                <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                    <div class="flex-1">
                                        <div class="font-medium">{{ $task->title }}</div>
                                        @if($task->assignees->isNotEmpty())
                                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                {{ __('app.labels.assigned_to') }}: {{ $task->assignees->pluck('name')->join(', ') }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-right ml-4">
                                        <div class="text-sm font-semibold">{{ number_format($task->percent_complete, 0) }}%</div>
                                        @if($task->isBlocked())
                                            <div class="text-xs text-orange-600">{{ __('app.labels.blocked') }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Timeline Overview --}}
                @if($timeline !== null && !empty($timeline['milestones']))
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ __('app.labels.milestones') }}</h3>
                        <div class="space-y-2">
                            @foreach($timeline['milestones'] as $milestone)
                                <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                    <div class="flex-1">
                                        <div class="font-medium">{{ $milestone['task_name'] }}</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                            {{ __('app.labels.scheduled') }}: {{ $milestone['scheduled_end'] }}
                                        </div>
                                    </div>
                                    <div class="text-right ml-4">
                                        <div class="text-sm font-semibold">{{ number_format($milestone['percent_complete'], 0) }}%</div>
                                        @if($milestone['is_critical'])
                                            <div class="text-xs text-red-600">{{ __('app.labels.critical') }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
