<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Export Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('app.sections.quick_export') }}
                </x-slot>
                
                <x-slot name="description">
                    {{ __('app.descriptions.quick_export_overview') }}
                </x-slot>

                <div class="space-y-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('app.descriptions.quick_export_features') }}
                    </div>
                    
                    <ul class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
                        <li>• {{ __('app.features.predefined_templates') }}</li>
                        <li>• {{ __('app.features.instant_processing') }}</li>
                        <li>• {{ __('app.features.common_formats') }}</li>
                    </ul>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    {{ __('app.sections.custom_export') }}
                </x-slot>
                
                <x-slot name="description">
                    {{ __('app.descriptions.custom_export_overview') }}
                </x-slot>

                <div class="space-y-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('app.descriptions.custom_export_features') }}
                    </div>
                    
                    <ul class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
                        <li>• {{ __('app.features.field_selection') }}</li>
                        <li>• {{ __('app.features.advanced_filtering') }}</li>
                        <li>• {{ __('app.features.format_options') }}</li>
                    </ul>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    {{ __('app.sections.export_management') }}
                </x-slot>
                
                <x-slot name="description">
                    {{ __('app.descriptions.export_management_overview') }}
                </x-slot>

                <div class="space-y-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('app.descriptions.export_management_features') }}
                    </div>
                    
                    <ul class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
                        <li>• {{ __('app.features.job_tracking') }}</li>
                        <li>• {{ __('app.features.download_management') }}</li>
                        <li>• {{ __('app.features.error_handling') }}</li>
                    </ul>

                    <div class="pt-2">
                        <x-filament::button
                            tag="a"
                            href="{{ route('filament.app.resources.export-jobs.index') }}"
                            size="sm"
                            color="gray"
                        >
                            {{ __('app.actions.view_export_jobs') }}
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Available Data Types -->
        <x-filament::section>
            <x-slot name="heading">
                {{ __('app.sections.available_data_types') }}
            </x-slot>
            
            <x-slot name="description">
                {{ __('app.descriptions.available_data_types') }}
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @php
                    $dataTypes = [
                        'Company' => [
                            'icon' => 'heroicon-o-building-office',
                            'label' => __('app.models.company'),
                            'description' => __('app.descriptions.company_export'),
                            'fields' => __('app.descriptions.company_fields'),
                        ],
                        'People' => [
                            'icon' => 'heroicon-o-users',
                            'label' => __('app.models.people'),
                            'description' => __('app.descriptions.people_export'),
                            'fields' => __('app.descriptions.people_fields'),
                        ],
                        'Opportunity' => [
                            'icon' => 'heroicon-o-currency-dollar',
                            'label' => __('app.models.opportunity'),
                            'description' => __('app.descriptions.opportunity_export'),
                            'fields' => __('app.descriptions.opportunity_fields'),
                        ],
                        'Task' => [
                            'icon' => 'heroicon-o-clipboard-document-list',
                            'label' => __('app.models.task'),
                            'description' => __('app.descriptions.task_export'),
                            'fields' => __('app.descriptions.task_fields'),
                        ],
                        'Note' => [
                            'icon' => 'heroicon-o-document-text',
                            'label' => __('app.models.note'),
                            'description' => __('app.descriptions.note_export'),
                            'fields' => __('app.descriptions.note_fields'),
                        ],
                    ];
                @endphp

                @foreach($dataTypes as $type => $config)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
                        <div class="flex items-center space-x-3">
                            <x-filament::icon
                                :icon="$config['icon']"
                                class="w-6 h-6 text-primary-500"
                            />
                            <h3 class="font-medium text-gray-900 dark:text-gray-100">
                                {{ $config['label'] }}
                            </h3>
                        </div>
                        
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $config['description'] }}
                        </p>
                        
                        <div class="text-xs text-gray-500 dark:text-gray-500">
                            <strong>{{ __('app.labels.available_fields') }}:</strong>
                            {{ $config['fields'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <!-- Export Formats -->
        <x-filament::section>
            <x-slot name="heading">
                {{ __('app.sections.supported_formats') }}
            </x-slot>
            
            <x-slot name="description">
                {{ __('app.descriptions.supported_formats') }}
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center space-x-3 mb-3">
                        <x-filament::icon
                            icon="heroicon-o-document-text"
                            class="w-6 h-6 text-green-500"
                        />
                        <h3 class="font-medium text-gray-900 dark:text-gray-100">CSV</h3>
                    </div>
                    
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        {{ __('app.descriptions.csv_format') }}
                    </p>
                    
                    <ul class="text-xs space-y-1 text-gray-500 dark:text-gray-500">
                        <li>• {{ __('app.features.csv_lightweight') }}</li>
                        <li>• {{ __('app.features.csv_compatible') }}</li>
                        <li>• {{ __('app.features.csv_customizable') }}</li>
                    </ul>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center space-x-3 mb-3">
                        <x-filament::icon
                            icon="heroicon-o-table-cells"
                            class="w-6 h-6 text-blue-500"
                        />
                        <h3 class="font-medium text-gray-900 dark:text-gray-100">Excel (XLSX)</h3>
                    </div>
                    
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        {{ __('app.descriptions.excel_format') }}
                    </p>
                    
                    <ul class="text-xs space-y-1 text-gray-500 dark:text-gray-500">
                        <li>• {{ __('app.features.excel_formatting') }}</li>
                        <li>• {{ __('app.features.excel_formulas') }}</li>
                        <li>• {{ __('app.features.excel_charts') }}</li>
                    </ul>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>