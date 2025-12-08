<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\Content\ProfanityFilterService;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;

/**
 * Filament page for testing and managing profanity filter.
 */
final class ProfanityFilterSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected string $view = 'filament.pages.profanity-filter-settings';

    protected static ?int $navigationSort = 999;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.profanity_filter');
    }

    public function getTitle(): string
    {
        return __('app.navigation.profanity_filter');
    }

    public function mount(): void
    {
        $this->form->fill([
            'language' => config('blasp.default_language', 'english'),
            'mask_character' => config('blasp.mask_character', '*'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('app.sections.test_profanity_filter'))
                    ->description(__('app.sections.test_profanity_filter_description'))
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('language')
                                ->label(__('app.labels.language'))
                                ->options([
                                    'english' => __('app.languages.english'),
                                    'spanish' => __('app.languages.spanish'),
                                    'german' => __('app.languages.german'),
                                    'french' => __('app.languages.french'),
                                    'all' => __('app.languages.all'),
                                ])
                                ->default(config('blasp.default_language', 'english'))
                                ->required()
                                ->live(),

                            TextInput::make('mask_character')
                                ->label(__('app.labels.mask_character'))
                                ->default('*')
                                ->maxLength(1)
                                ->required(),
                        ]),

                        Textarea::make('test_text')
                            ->label(__('app.labels.text_to_test'))
                            ->placeholder(__('app.placeholders.enter_text_to_test'))
                            ->rows(5)
                            ->columnSpanFull()
                            ->required(),

                        Textarea::make('result_text')
                            ->label(__('app.labels.cleaned_text'))
                            ->rows(5)
                            ->columnSpanFull()
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Section::make(__('app.sections.profanity_statistics'))
                    ->description(__('app.sections.profanity_statistics_description'))
                    ->schema([
                        TextInput::make('profanity_count')
                            ->label(__('app.labels.profanities_found'))
                            ->disabled()
                            ->dehydrated(false),

                        Textarea::make('unique_profanities')
                            ->label(__('app.labels.unique_profanities'))
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')
                ->label(__('app.actions.test_filter'))
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action('testFilter'),

            Action::make('clearCache')
                ->label(__('app.actions.clear_cache'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    $service = resolve(ProfanityFilterService::class);
                    $service->clearCache();

                    Notification::make()
                        ->title(__('app.notifications.cache_cleared'))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function testFilter(): void
    {
        try {
            $data = $this->form->getState();

            if (empty($data['test_text'])) {
                Notification::make()
                    ->title(__('app.notifications.no_text_provided'))
                    ->warning()
                    ->send();

                return;
            }

            $service = resolve(ProfanityFilterService::class);

            $result = $data['language'] === 'all'
                ? $service->checkAllLanguages($data['test_text'], $data['mask_character'])
                : $service->analyze($data['test_text'], $data['language'], $data['mask_character']);

            $this->form->fill([
                ...$data,
                'result_text' => $result['clean_text'],
                'profanity_count' => $result['count'],
                'unique_profanities' => implode(', ', $result['unique_profanities']),
            ]);

            if ($result['has_profanity']) {
                Notification::make()
                    ->title(__('app.notifications.profanity_detected'))
                    ->body(__('app.notifications.profanity_detected_body', [
                        'count' => $result['count'],
                    ]))
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title(__('app.notifications.no_profanity_detected'))
                    ->success()
                    ->send();
            }
        } catch (Halt) {
            return;
        }
    }
}
