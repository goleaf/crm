<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\SeederGeneratorService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

final class SeederGenerator extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 1010;

    protected string $view = 'filament.pages.seeder-generator';

    public ?array $data = [];

    private SeederGeneratorService $generator;

    public function mount(SeederGeneratorService $generator): void
    {
        $this->generator = $generator;

        $this->form->fill([
            'mode' => 'model',
            'include_relations' => true,
            'order_direction' => 'asc',
            'add_to_database_seeder' => true,
        ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.seeder_generator');
    }

    public function getTitle(): string
    {
        return __('app.navigation.seeder_generator');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $user?->hasVerifiedEmail()
            && ($user->ownsTeam($tenant) || $user->hasTeamRole($tenant, 'admin'));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('app.sections.generator_configuration'))
                    ->schema([
                        Forms\Components\ToggleButtons::make('mode')
                            ->label(__('app.labels.mode'))
                            ->options([
                                'model' => __('app.labels.model_mode'),
                                'table' => __('app.labels.table_mode'),
                            ])
                            ->inline()
                            ->required()
                            ->live(),
                        Grid::make([
                            'default' => 1,
                            'xl' => 2,
                        ])
                            ->schema([
                                Forms\Components\Select::make('models')
                                    ->label(__('app.labels.models'))
                                    ->options(fn (): array => $this->generator->modelOptions())
                                    ->searchable()
                                    ->multiple()
                                    ->visible(fn (Forms\Get $get): bool => $get('mode') === 'model')
                                    ->required(fn (Forms\Get $get): bool => $get('mode') === 'model')
                                    ->placeholder(__('app.placeholders.select_models')),
                                Forms\Components\Select::make('tables')
                                    ->label(__('app.labels.tables'))
                                    ->options(fn (): array => $this->generator->tableOptions())
                                    ->searchable()
                                    ->multiple()
                                    ->visible(fn (Forms\Get $get): bool => $get('mode') === 'table')
                                    ->required(fn (Forms\Get $get): bool => $get('mode') === 'table')
                                    ->placeholder(__('app.placeholders.select_tables')),
                            ]),
                    ]),
                Section::make(__('app.sections.relations'))
                    ->visible(fn (Forms\Get $get): bool => $get('mode') === 'model')
                    ->schema([
                        Forms\Components\Toggle::make('include_relations')
                            ->label(__('app.labels.include_relations'))
                            ->inline(false),
                        Forms\Components\TagsInput::make('relations')
                            ->label(__('app.labels.relations'))
                            ->placeholder('tasks,notes,activities')
                            ->visible(fn (Forms\Get $get): bool => $get('include_relations') === true),
                        Forms\Components\TextInput::make('relations_limit')
                            ->label(__('app.labels.relations_limit'))
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn (Forms\Get $get): bool => $get('include_relations') === true),
                    ]),
                Section::make(__('app.sections.filters'))
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'xl' => 2,
                        ])
                            ->schema([
                                Forms\Components\TagsInput::make('ids')
                                    ->label(__('app.labels.ids'))
                                    ->placeholder('1,2,3'),
                                Forms\Components\TagsInput::make('ignore_ids')
                                    ->label(__('app.labels.ignore_ids'))
                                    ->placeholder('4,5,6'),
                                Forms\Components\TagsInput::make('fields')
                                    ->label(__('app.labels.fields'))
                                    ->placeholder('id,name,email'),
                                Forms\Components\TagsInput::make('ignore_fields')
                                    ->label(__('app.labels.ignore_fields'))
                                    ->placeholder('created_at,updated_at'),
                            ]),
                        Grid::make([
                            'default' => 1,
                            'xl' => 3,
                        ])
                            ->schema([
                                Forms\Components\TextInput::make('where.field')
                                    ->label(__('app.labels.where_field')),
                                Forms\Components\Select::make('where.operator')
                                    ->label(__('app.labels.where_operator'))
                                    ->options([
                                        '=' => '=',
                                        '!=' => '!=',
                                        '>' => '>',
                                        '<' => '<',
                                        '>=' => '>=',
                                        '<=' => '<=',
                                        'like' => 'like',
                                    ])
                                    ->default('='),
                                Forms\Components\TextInput::make('where.value')
                                    ->label(__('app.labels.where_value')),
                            ]),
                        Grid::make([
                            'default' => 1,
                            'xl' => 2,
                        ])
                            ->schema([
                                Forms\Components\TextInput::make('where_in.field')
                                    ->label(__('app.labels.where_in_field')),
                                Forms\Components\TagsInput::make('where_in.values')
                                    ->label(__('app.labels.where_in_values'))
                                    ->placeholder('north,south'),
                            ]),
                        Grid::make([
                            'default' => 1,
                            'xl' => 2,
                        ])
                            ->schema([
                                Forms\Components\TextInput::make('where_not_in.field')
                                    ->label(__('app.labels.where_not_in_field')),
                                Forms\Components\TagsInput::make('where_not_in.values')
                                    ->label(__('app.labels.where_not_in_values'))
                                    ->placeholder('draft,canceled'),
                            ]),
                    ]),
                Section::make(__('app.sections.output'))
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'xl' => 3,
                        ])
                            ->schema([
                                Forms\Components\TextInput::make('order_by_field')
                                    ->label(__('app.labels.order_by_field')),
                                Forms\Components\Select::make('order_direction')
                                    ->label(__('app.labels.order_direction'))
                                    ->options([
                                        'asc' => __('app.labels.ascending'),
                                        'desc' => __('app.labels.descending'),
                                    ])
                                    ->default('asc'),
                                Forms\Components\TextInput::make('limit')
                                    ->label(__('app.labels.limit'))
                                    ->numeric()
                                    ->minValue(1),
                            ]),
                        Forms\Components\TextInput::make('output')
                            ->label(__('app.labels.output_path'))
                            ->placeholder('Seeders/Exports/Leads'),
                        Forms\Components\Toggle::make('add_to_database_seeder')
                            ->label(__('app.labels.add_to_database_seeder'))
                            ->inline(false)
                            ->default(true),
                        Forms\Components\Placeholder::make('command_preview')
                            ->label(__('app.labels.command_preview'))
                            ->content(fn (): string => $this->commandPreview())
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Validator::make($state, [
            'mode' => ['required', Rule::in(['model', 'table'])],
            'models' => [Rule::requiredIf(fn (): bool => ($state['mode'] ?? null) === 'model'), 'array', 'min:1'],
            'models.*' => ['string'],
            'tables' => [Rule::requiredIf(fn (): bool => ($state['mode'] ?? null) === 'table'), 'array', 'min:1'],
            'tables.*' => ['string'],
            'order_direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'limit' => ['nullable', 'integer', 'min:1'],
            'relations_limit' => ['nullable', 'integer', 'min:1'],
        ])->validate();

        try {
            $options = $this->generator->buildOptions($state);
            $output = $this->generator->run($options);

            Notification::make()
                ->title(__('app.messages.seed_generation_success'))
                ->body($output ?: __('app.messages.seed_generation_success'))
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(__('app.messages.seed_generation_failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function commandPreview(): string
    {
        $options = $this->generator->buildOptions($this->form->getState());

        $compiled = collect($options)
            ->map(function ($value, string $key): ?string {
                if (is_bool($value)) {
                    return $value ? $key : null;
                }

                return "{$key}=\"{$value}\"";
            })
            ->filter()
            ->implode(' ');

        return trim(sprintf('php artisan seed:generate %s', $compiled));
    }
}
