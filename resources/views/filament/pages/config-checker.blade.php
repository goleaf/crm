<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        @livewire(\App\Filament\Widgets\ConfigStatusWidget::class)
    </div>

    @if(!empty($checkResult['issues']))
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
            <span class="font-medium">Issues Detected!</span> The following configuration keys are referenced but not
            defined:
        </div>

        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">
                            Issue Detail
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($checkResult['issues'] as $issue)
                        <tr
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-mono">
                                {{ $issue }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400"
            role="alert">
            <span class="font-medium">All Good!</span> No missing configuration keys were detected.
        </div>
    @endif

    <div class="mt-6">
        <h3 class="font-bold text-lg mb-2">Raw Output</h3>
        <pre
            class="bg-gray-100 dark:bg-gray-900 p-4 rounded text-xs font-mono overflow-auto max-h-64">{{ $checkResult['raw_output'] ?? 'No output available' }}</pre>
    </div>
</x-filament-panels::page>