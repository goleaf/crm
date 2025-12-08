<x-filament-panels::page>
    <div class="space-y-6">
        @if($auditResult)
            {{-- Security Status Overview --}}
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('app.labels.security_status') }}
                </x-slot>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    {{-- Vulnerabilities Card --}}
                    <div class="rounded-lg border p-4 {{ $auditResult['has_vulnerabilities'] ? 'border-danger-600 bg-danger-50 dark:bg-danger-950' : 'border-success-600 bg-success-50 dark:bg-success-950' }}">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium {{ $auditResult['has_vulnerabilities'] ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">
                                    {{ __('app.labels.vulnerabilities') }}
                                </p>
                                <p class="mt-1 text-3xl font-bold {{ $auditResult['has_vulnerabilities'] ? 'text-danger-900 dark:text-danger-100' : 'text-success-900 dark:text-success-100' }}">
                                    {{ $auditResult['vulnerability_count'] }}
                                </p>
                            </div>
                            <x-filament::icon
                                :icon="$auditResult['has_vulnerabilities'] ? 'heroicon-o-shield-exclamation' : 'heroicon-o-shield-check'"
                                class="h-12 w-12 {{ $auditResult['has_vulnerabilities'] ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}"
                            />
                        </div>
                    </div>

                    {{-- Packages Audited Card --}}
                    <div class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-600 dark:bg-gray-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ __('app.labels.packages_audited') }}
                                </p>
                                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ $auditResult['packages_audited'] }}
                                </p>
                            </div>
                            <x-filament::icon
                                icon="heroicon-o-cube"
                                class="h-12 w-12 text-gray-600 dark:text-gray-400"
                            />
                        </div>
                    </div>

                    {{-- Last Audit Card --}}
                    <div class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-600 dark:bg-gray-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ __('app.labels.last_audit') }}
                                </p>
                                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">
                                    {{ $auditResult['last_audit'] ?? __('app.labels.never') }}
                                </p>
                            </div>
                            <x-filament::icon
                                icon="heroicon-o-clock"
                                class="h-12 w-12 text-gray-600 dark:text-gray-400"
                            />
                        </div>
                    </div>
                </div>
            </x-filament::section>

            {{-- Vulnerabilities List --}}
            @if($auditResult['has_vulnerabilities'] && !empty($auditResult['vulnerabilities']))
                <x-filament::section>
                    <x-slot name="heading">
                        {{ __('app.labels.detected_vulnerabilities') }}
                    </x-slot>

                    <div class="space-y-4">
                        @foreach($auditResult['vulnerabilities'] as $vulnerability)
                            <div class="rounded-lg border border-danger-300 bg-danger-50 p-4 dark:border-danger-700 dark:bg-danger-950">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-danger-900 dark:text-danger-100">
                                            {{ $vulnerability['package'] ?? __('app.labels.unknown_package') }}
                                        </h4>
                                        <p class="mt-1 text-sm text-danger-700 dark:text-danger-300">
                                            {{ $vulnerability['title'] ?? $vulnerability['message'] ?? __('app.labels.no_description') }}
                                        </p>
                                        @if(isset($vulnerability['cve']))
                                            <p class="mt-2 text-xs font-mono text-danger-600 dark:text-danger-400">
                                                CVE: {{ $vulnerability['cve'] }}
                                            </p>
                                        @endif
                                        @if(isset($vulnerability['link']))
                                            <a
                                                href="{{ $vulnerability['link'] }}"
                                                target="_blank"
                                                class="mt-2 inline-flex items-center text-sm text-danger-600 hover:text-danger-700 dark:text-danger-400 dark:hover:text-danger-300"
                                            >
                                                {{ __('app.actions.view_details') }}
                                                <x-filament::icon
                                                    icon="heroicon-o-arrow-top-right-on-square"
                                                    class="ml-1 h-4 w-4"
                                                />
                                            </a>
                                        @endif
                                    </div>
                                    @if(isset($vulnerability['severity']))
                                        <span class="ml-4 inline-flex items-center rounded-full px-3 py-1 text-xs font-medium
                                            {{ match($vulnerability['severity']) {
                                                'critical' => 'bg-danger-600 text-white',
                                                'high' => 'bg-danger-500 text-white',
                                                'medium' => 'bg-warning-500 text-white',
                                                'low' => 'bg-info-500 text-white',
                                                default => 'bg-gray-500 text-white',
                                            } }}">
                                            {{ ucfirst($vulnerability['severity']) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif

            {{-- Security Recommendations --}}
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('app.labels.security_recommendations') }}
                </x-slot>

                <div class="prose prose-sm dark:prose-invert max-w-none">
                    <ul>
                        <li>{{ __('app.messages.run_composer_update') }}</li>
                        <li>{{ __('app.messages.review_changelogs') }}</li>
                        <li>{{ __('app.messages.test_after_updates') }}</li>
                        <li>{{ __('app.messages.monitor_security_advisories') }}</li>
                        <li>{{ __('app.messages.enable_automated_audits') }}</li>
                    </ul>
                </div>
            </x-filament::section>
        @else
            {{-- No Audit Results --}}
            <x-filament::section>
                <div class="text-center py-12">
                    <x-filament::icon
                        icon="heroicon-o-shield-check"
                        class="mx-auto h-16 w-16 text-gray-400"
                    />
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ __('app.labels.no_audit_results') }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('app.messages.run_first_audit') }}
                    </p>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
