<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\Search\AdvancedSearchService;
use App\Services\Search\SavedSearchService;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

final class AdvancedSearch extends Page implements HasForms
{
    use InteractsWithForms;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-magnifying-glass';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.data_management');
    }

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.pages.advanced-search';

    public ?array $data = [];

    public ?LengthAwarePaginator $searchResults = null;

    public array $suggestions = [];

    public function __construct(
        private readonly AdvancedSearchService $advancedSearch,
        private readonly SavedSearchService $savedSearch,
    ) {
        parent::__construct();
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.advanced_search');
    }

    public function getTitle(): string
    {
        return __('app.pages.advanced_search');
    }

    public function mount(): void
    {
        $this->form->fill([
            'query' => '',
            'module' => null,
            'filters' => [],
            'sort' => 'relevance',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('query')
                            ->label(__('app.labels.search_query'))
                            ->placeholder(__('app.placeholders.enter_search_terms'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (?string $state) => $this->updateSuggestions($state))
                            ->columnSpanFull(),

                        Select::make('module')
                            ->label(__('app.labels.search_module'))
                            ->placeholder(__('app.placeholders.all_modules'))
                            ->options($this->getModuleOptions())
                            ->native(false),

                        Select::make('sort')
                            ->label(__('app.labels.sort_by'))
                            ->options([
                                'relevance' => __('app.labels.relevance'),
                                'date_desc' => __('app.labels.newest_first'),
                                'date_asc' => __('app.labels.oldest_first'),
                                'name_asc' => __('app.labels.name_a_z'),
                                'name_desc' => __('app.labels.name_z_a'),
                            ])
                            ->default('relevance')
                            ->native(false),
                    ]),

                Repeater::make('filters')
                    ->label(__('app.labels.advanced_filters'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('field')
                                    ->label(__('app.labels.field'))
                                    ->options($this->getFieldOptions())
                                    ->required()
                                    ->live()
                                    ->native(false),

                                Select::make('operator')
                                    ->label(__('app.labels.operator'))
                                    ->options(fn ($get): array => $this->getOperatorOptions($get('field')))
                                    ->required()
                                    ->native(false),

                                TextInput::make('value')
                                    ->label(__('app.labels.value'))
                                    ->required(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->addActionLabel(__('app.actions.add_filter'))
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('search')
                ->label(__('app.actions.search'))
                ->icon('heroicon-o-magnifying-glass')
                ->action('performSearch')
                ->keyBindings(['cmd+enter', 'ctrl+enter']),

            Action::make('save_search')
                ->label(__('app.actions.save_search'))
                ->icon('heroicon-o-bookmark')
                ->form([
                    TextInput::make('name')
                        ->label(__('app.labels.search_name'))
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $this->saveSearch($data['name']);
                })
                ->visible(fn (): bool => ! empty($this->data['query'])),

            Action::make('load_saved_search')
                ->label(__('app.actions.load_saved_search'))
                ->icon('heroicon-o-folder-open')
                ->form([
                    Select::make('saved_search_id')
                        ->label(__('app.labels.saved_search'))
                        ->options($this->getSavedSearchOptions())
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    $this->loadSavedSearch($data['saved_search_id']);
                })
                ->modalWidth(MaxWidth::Medium),
        ];
    }

    public function performSearch(): void
    {
        $query = $this->data['query'] ?? '';

        if (empty($query)) {
            Notification::make()
                ->title(__('app.notifications.search_query_required'))
                ->warning()
                ->send();

            return;
        }

        try {
            $this->searchResults = $this->advancedSearch->search(
                query: $query,
                module: $this->data['module'] ?? null,
                filters: $this->data['filters'] ?? [],
                options: [
                    'sort' => $this->data['sort'] ?? 'relevance',
                ],
                perPage: 25,
            );

            if ($this->searchResults->total() === 0) {
                Notification::make()
                    ->title(__('app.notifications.no_search_results'))
                    ->body(__('app.notifications.try_different_terms'))
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('app.notifications.search_error'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function updateSuggestions(?string $query): void
    {
        if (in_array($query, [null, '', '0'], true) || strlen($query) < 2) {
            $this->suggestions = [];

            return;
        }

        $this->suggestions = $this->advancedSearch
            ->getSuggestions($query, $this->data['module'] ?? null, 5)
            ->all();
    }

    public function applySuggestion(string $term): void
    {
        $this->data['query'] = $term;
        $this->form->fill($this->data);
        $this->performSearch();
    }

    private function saveSearch(string $name): void
    {
        try {
            $this->savedSearch->save(
                user: Auth::user(),
                name: $name,
                resource: 'advanced_search',
                query: $this->data['query'] ?? null,
                filters: [
                    'module' => $this->data['module'] ?? null,
                    'filters' => $this->data['filters'] ?? [],
                    'sort' => $this->data['sort'] ?? 'relevance',
                ],
            );

            Notification::make()
                ->title(__('app.notifications.search_saved'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('app.notifications.save_search_error'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function loadSavedSearch(int $savedSearchId): void
    {
        $savedSearch = $this->savedSearch
            ->list(Auth::user(), 'advanced_search')
            ->firstWhere('id', $savedSearchId);

        if (! $savedSearch) {
            Notification::make()
                ->title(__('app.notifications.saved_search_not_found'))
                ->danger()
                ->send();

            return;
        }

        $this->data = [
            'query' => $savedSearch->query ?? '',
            'module' => $savedSearch->filters['module'] ?? null,
            'filters' => $savedSearch->filters['filters'] ?? [],
            'sort' => $savedSearch->filters['sort'] ?? 'relevance',
        ];

        $this->form->fill($this->data);
        $this->performSearch();

        Notification::make()
            ->title(__('app.notifications.search_loaded'))
            ->success()
            ->send();
    }

    private function getModuleOptions(): array
    {
        return collect($this->advancedSearch->getSearchableModules())
            ->mapWithKeys(fn ($config, $key): array => [$key => $config['label']])
            ->toArray();
    }

    private function getFieldOptions(): array
    {
        $options = [];
        foreach ($this->advancedSearch->getSearchableModules() as $moduleKey => $moduleConfig) {
            foreach ($moduleConfig['fields'] as $fieldKey => $fieldConfig) {
                $options["{$moduleKey}.{$fieldKey}"] = "{$moduleConfig['label']} - {$fieldConfig['label']}";
            }
        }

        return $options;
    }

    private function getOperatorOptions(?string $field): array
    {
        if (! $field) {
            return [];
        }

        [$module, $fieldName] = explode('.', $field, 2);
        $fieldConfig = $this->advancedSearch->getSearchableModules()[$module]['fields'][$fieldName] ?? null;

        if (! $fieldConfig) {
            return [];
        }

        $fieldType = $fieldConfig['type'];
        $operators = $this->advancedSearch->getAvailableOperators();

        return collect($operators)
            ->filter(fn ($operator): bool => in_array($fieldType, $operator['types']))
            ->mapWithKeys(fn ($operator, $key): array => [$key => $operator['label']])
            ->toArray();
    }

    private function getSavedSearchOptions(): array
    {
        return $this->savedSearch
            ->list(Auth::user(), 'advanced_search')
            ->pluck('name', 'id')
            ->toArray();
    }
}
