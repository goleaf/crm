<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Search Form -->
        <x-filament::section>
            <x-slot name="heading">
                {{ __('app.labels.search_criteria') }}
            </x-slot>

            {{ $this->form }}

            <!-- Search Suggestions -->
            @if(!empty($this->suggestions))
                <div class="mt-4">
                    <x-filament::fieldset>
                        <x-slot name="label">
                            {{ __('app.labels.suggestions') }}
                        </x-slot>

                        <div class="flex flex-wrap gap-2">
                            @foreach($this->suggestions as $suggestion)
                                <x-filament::button
                                    size="sm"
                                    color="gray"
                                    wire:click="applySuggestion('{{ $suggestion['term'] }}')"
                                >
                                    {{ $suggestion['term'] }}
                                </x-filament::button>
                            @endforeach
                        </div>
                    </x-filament::fieldset>
                </div>
            @endif
        </x-filament::section>

        <!-- Search Results -->
        @if($this->searchResults)
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('app.labels.search_results') }}
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                        ({{ number_format($this->searchResults->total()) }} {{ __('app.labels.results') }})
                    </span>
                </x-slot>

                @if($this->searchResults->count() > 0)
                    <div class="space-y-4">
                        @foreach($this->searchResults as $result)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <x-filament::badge
                                                color="primary"
                                                size="sm"
                                            >
                                                {{ ucfirst(str_replace('_', ' ', $result->search_module)) }}
                                            </x-filament::badge>
                                            
                                            @if(isset($result->name))
                                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $result->name }}
                                                </h3>
                                            @elseif(isset($result->title))
                                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $result->title }}
                                                </h3>
                                            @elseif(isset($result->subject))
                                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $result->subject }}
                                                </h3>
                                            @endif
                                        </div>

                                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                            @if(isset($result->description) && $result->description)
                                                <p>{{ Str::limit($result->description, 150) }}</p>
                                            @endif

                                            @if(isset($result->primary_email) && $result->primary_email)
                                                <p><strong>{{ __('app.labels.email') }}:</strong> {{ $result->primary_email }}</p>
                                            @endif

                                            @if(isset($result->phone) && $result->phone)
                                                <p><strong>{{ __('app.labels.phone') }}:</strong> {{ $result->phone }}</p>
                                            @endif

                                            @if(isset($result->job_title) && $result->job_title)
                                                <p><strong>{{ __('app.labels.job_title') }}:</strong> {{ $result->job_title }}</p>
                                            @endif

                                            @if(isset($result->value) && $result->value)
                                                <p><strong>{{ __('app.labels.value') }}:</strong> {{ number_format($result->value, 2) }}</p>
                                            @endif

                                            @if(isset($result->status) && $result->status)
                                                <p><strong>{{ __('app.labels.status') }}:</strong> {{ $result->status }}</p>
                                            @endif

                                            @if(isset($result->created_at))
                                                <p><strong>{{ __('app.labels.created') }}:</strong> {{ $result->created_at->format('M j, Y') }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="ml-4">
                                        @php
                                            $resourceUrl = match($result->search_module) {
                                                'companies' => route('filament.app.resources.companies.view', $result->id),
                                                'people' => route('filament.app.resources.people.view', $result->id),
                                                'opportunities' => route('filament.app.resources.opportunities.view', $result->id),
                                                'tasks' => route('filament.app.resources.tasks.view', $result->id),
                                                'support_cases' => route('filament.app.resources.support-cases.view', $result->id),
                                                default => null,
                                            };
                                        @endphp

                                        @if($resourceUrl)
                                            <x-filament::button
                                                tag="a"
                                                href="{{ $resourceUrl }}"
                                                size="sm"
                                                color="primary"
                                            >
                                                {{ __('app.actions.view') }}
                                            </x-filament::button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $this->searchResults->links() }}
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-heroicon-o-magnifying-glass class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ __('app.labels.no_results_found') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('app.messages.try_different_search_terms') }}
                        </p>
                    </div>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>