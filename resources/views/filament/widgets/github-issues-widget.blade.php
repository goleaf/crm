<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('Recent GitHub Issues') }}
        </x-slot>

        <div class="space-y-4">
            @if($this->getIssues()->isEmpty())
                <div class="text-sm text-gray-500 text-center py-4">
                    {{ __('No open issues found or GitHub integration not configured.') }}
                </div>
            @else
                <div class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach($this->getIssues() as $issue)
                        <div class="py-2 flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <a href="{{ $issue['html_url'] }}" target="_blank"
                                    class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                    #{{ $issue['number'] }} {{ $issue['title'] }}
                                </a>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($issue['created_at'])->diffForHumans() }} by
                                        {{ $issue['user']['login'] }}
                                    </span>
                                    <div class="flex gap-1">
                                        @foreach($issue['labels'] as $label)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                style="background-color: #{{ $label['color'] }}; color: {{ \App\Support\Helpers\ColorHelper::isLight('#' . $label['color']) ? '#000' : '#fff' }}">
                                                {{ $label['name'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>