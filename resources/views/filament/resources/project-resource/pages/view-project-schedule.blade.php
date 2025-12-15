<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Gantt Chart Export Section --}}
        <x-filament::section>
            <x-slot name="heading">
                {{ __('app.labels.gantt_chart_data') }}
            </x-slot>

            <x-slot name="description">
                {{ __('app.messages.gantt_export_description') }}
            </x-slot>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-medium">{{ __('app.labels.export_for_gantt') }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('app.messages.gantt_export_help') }}
                        </p>
                    </div>
                    <x-filament::button
                        wire:click="$dispatch('export-gantt')"
                        icon="heroicon-o-arrow-down-tray"
                    >
                        {{ __('app.actions.export_json') }}
                    </x-filament::button>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <pre class="text-xs overflow-auto max-h-96">{{ json_encode($ganttData, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        </x-filament::section>

        {{-- Budget Summary Section --}}
        <x-filament::section>
            <x-slot name="heading">
                {{ __('app.labels.budget_summary') }}
            </x-slot>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.labels.budget') }}</div>
                        <div class="text-xl font-bold mt-1">
                            @if($budgetSummary['budget'])
                                {{ number_format($budgetSummary['budget'], 2) }} {{ $budgetSummary['currency'] }}
                            @else
                                {{ __('app.labels.not_set') }}
                            @endif
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.labels.actual_cost') }}</div>
                        <div class="text-xl font-bold mt-1">
                            {{ number_format($budgetSummary['actual_cost'], 2) }} {{ $budgetSummary['currency'] }}
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.labels.variance') }}</div>
                        <div class="text-xl font-bold mt-1">
                            @if($budgetSummary['variance'] !== null)
                                <span class="{{ $budgetSummary['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($budgetSummary['variance'], 2) }} {{ $budgetSummary['currency'] }}
                                </span>
                            @else
                                {{ __('app.labels.not_applicable') }}
                            @endif
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.labels.utilization') }}</div>
                        <div class="text-xl font-bold mt-1">
                            @if($budgetSummary['utilization_percentage'] !== null)
                                <span class="{{ $budgetSummary['is_over_budget'] ? 'text-red-600' : 'text-green-600' }}">
                                    {{ number_format($budgetSummary['utilization_percentage'], 1) }}%
                                </span>
                            @else
                                {{ __('app.labels.not_applicable') }}
                            @endif
                        </div>
                    </div>
                </div>

                @if(!empty($budgetSummary['task_breakdown']))
                    <div class="mt-6">
                        <h4 class="font-medium mb-3">{{ __('app.labels.task_breakdown') }}</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('app.labels.task') }}
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('app.labels.hours') }}
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('app.labels.amount') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($budgetSummary['task_breakdown'] as $task)
                                        <tr>
                                            <td class="px-4 py-3 text-sm">{{ $task['task_name'] }}</td>
                                            <td class="px-4 py-3 text-sm text-right">{{ number_format($task['billable_hours'], 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-right">
                                                {{ number_format($task['billing_amount'], 2) }} {{ $budgetSummary['currency'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="font-bold bg-gray-50 dark:bg-gray-800">
                                        <td class="px-4 py-3 text-sm">{{ __('app.labels.total') }}</td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            {{ number_format($budgetSummary['total_billable_hours'], 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            {{ number_format($budgetSummary['actual_cost'], 2) }} {{ $budgetSummary['currency'] }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
