<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\Content\ProfanityFilterService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ProfanityFilterSettings extends Page implements HasForms
{
    use InteractsWithForms;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-shield-check';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    public static function getNavigationLabel(): string
    {
        return 'Profanity Filter';
    }

    public function getTitle(): string
    {
        return 'Profanity Filter Settings';
    }

    protected static string $view = 'filament.pages.profanity-filter-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Test Profanity Filter')
                    ->schema([
                        Grid::make(2)->schema([
                            Textarea::make('test_text')
                                ->label('Text to Check')
                                ->required()
                                ->rows(5),

                            Select::make('language')
                                ->options([
                                    'english' => 'English',
                                    'spanish' => 'Spanish',
                                    'german' => 'German',
                                    'french' => 'French',
                                    'all' => 'All Languages',
                                ])
                                ->default('english')
                                ->required(),
                        ]),
                    ]),
            ]);
    }

    public function testFilter(): void
    {
        $data = $this->form->getState();
        $service = resolve(ProfanityFilterService::class);

        $text = $data['test_text'];
        $language = $data['language'];

        $result = $service->validateAndClean($text, $language);

        if ($result['valid']) {
            Notification::make()->success()->title('No profanity found')->send();
        } else {
            Notification::make()
                ->warning()
                ->title('Profanity Found!')
                ->body('Cleaned text: ' . $result['clean_text'])
                ->persistent()
                ->send();
        }
    }

    public function clearCache(): void
    {
        resolve(ProfanityFilterService::class)->clearCache();
        Notification::make()->success()->title('Cache Cleared')->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear_cache')
                ->label('Clear Cache')
                ->action('clearCache')
                ->color('danger')
                ->requiresConfirmation(),
        ];
    }
}
