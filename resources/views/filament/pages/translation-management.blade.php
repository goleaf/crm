<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        @livewire(\App\Filament\Widgets\TranslationStatusWidget::class)
    </div>

    <div class="mt-6 prose dark:prose-invert max-w-none">
        <h3>{{ __('app.labels.translation_management_guide') }}</h3>
        <p>
            {{ __('app.labels.translation_management_description') }}
        </p>
        <ul>
            <li>
                <strong>{{ __('app.actions.import_translations') }}:</strong> {{ __('app.labels.import_description') }}
            </li>
            <li>
                <strong>{{ __('app.actions.export_translations') }}:</strong> {{ __('app.labels.export_description') }}
            </li>
            <li>
                <strong>{{ __('app.actions.open_translation_ui') }}:</strong> {{ __('app.labels.ui_description') }}
            </li>
        </ul>
    </div>
</x-filament-panels::page>