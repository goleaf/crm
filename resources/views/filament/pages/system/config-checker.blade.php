<x-filament-panels::page>
    @php
        $status = $this->checkResults['status'] ?? 'unknown';
        $issues = $this->checkResults['issues'] ?? [];
        $checkedAt = $this->checkResults['checked_at'] ?? null;
    @endphp

    <div class="grid gap-6">
        <x-filament::section>
            <div class="flex items-center gap-4">
                <div @class([
                    'flex h-12 w-12 items-center justify-center rounded-full',
                    'bg-success-100 text-success-600' => $status === 'healthy',
                    'bg-danger-100 text-danger-600' => $status === 'issues_found',
                    'bg-gray-100 text-gray-500' => $status === 'unknown',
                ])>
                    @if($status === 'healthy')
                        <x-heroicon-o-check-circle class="h-8 w-8" />
                    @elseif($status === 'issues_found')
                        <x-heroicon-o-exclamation-triangle class="h-8 w-8" />
                    @else
                        <x-heroicon-o-question-mark-circle class="h-8 w-8" />
                    @endif
                </div>

                <div>
                    <h2 class="text-lg font-bold">
                        @if($status === 'healthy')
                            All configuration keys exist.
                        @elseif($status === 'issues_found')
                            Found {{ count($issues) }} missing configuration keys.
                        @else
                            Status unknown.
                        @endif
                    </h2>
                    <p class="text-sm text-gray-500">
                        Last checked: {{ $checkedAt ? \Carbon\Carbon::parse($checkedAt)->diffForHumans() : 'Never' }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        @if(count($issues) > 0)
            <x-filament::section>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-500">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                            <tr>
                                <th class="px-6 py-3">File</th>
                                <th class="px-6 py-3">Line</th>
                                <th class="px-6 py-3">Key</th>
                                <th class="px-6 py-3">Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($issues as $issue)
                                <tr class="border-b bg-white hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{ $issue['file'] }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $issue['line'] }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <code class="rounded bg-gray-100 px-2 py-1 text-danger-600">{{ $issue['key'] }}</code>
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $issue['method'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        <div class="text-xs text-gray-400">
            <p>Note: Some "missing" keys might be false positives if they are dynamically generated or loaded at runtime
                outside standard config files.</p>
        </div>
    </div>
</x-filament-panels::page>