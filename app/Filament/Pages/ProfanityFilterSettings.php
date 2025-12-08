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

    public function getView(): string
    {
        return 'filament.pages.profanity-filter-settings';
    }

    // If not using a view, we can use schema() if supported by Page in v4??
    // Actually standard Pages use views usually, unless using `ManageSettings` cluster style with form.
    // Let's try to use a simple view or if there's a Schema wrapper for Pages.
    // But verify if `filament.pages.profanity-filter-settings` exists? No.
    // I should create the view or use a `ManagePreferences` style page if it was a settings page.
    // But this is an admin tool page.
    // Let's create the view file or just standard form page pattern.

    // Wait, the prompt implies "integrate it in filament v4".
    // I will write the view file too.

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data') // Optional if we bind validly
            ->components([
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

    // Wait, Page doesn't have `form(Schema $schema)` method standardly like Resources.
    // Standard Pages use `public function form(Form $form): Form`.
    // But v4 conventions say "All components now use Filament\Schemas\Schema".
    // So usually we use `schema` now?
    // Let's check `filament-conventions.md` line 24 "Page layouts now use schemas instead of Blade views".
    // Ah! So I should override `schema()` or `content()`?
    // Line 741 in conventions: `public function content(): Schema` inside `EditRecord`.
    // But for a generic Page?
    // Usually we override `public function getSchema(): Schema`? No.
    // Let's stick to standard `HasForms` with `form` method using Schema if compatible, or just standard Form.
    // But the new convention says "Override infolist() methods with Filament\Schemas\Schema".
    // It doesn't explicitly say `form()` signature changes for Pages, but presumably yes if "All components now use Schema".
    // Let's use `public function form(Schema $schema): Schema` and see if `HasForms` supports it?
    // Actually `HasForms` interface usually demands `form(Form $form): Form`.
    // If v4 changed this, the interface would change.
    // Let's assume `Form` is an alias or wrapper, OR we use `Schema` inside.
    // NOTE: conventions say "Form, Infolist, and Layout components live in the same namespace".

    // Let's try to follow the `EditRecord` pattern from conventions: `content(): Schema`.
    // But `Page` class?
    // Let's assume standard `view` usage is safe, but conventions say "Page layouts now use schemas instead of Blade views".
    // So I should probably define `public function schema(): Schema`?
    // Or `public function getHeaderActions()` etc is standard.

    // Let's just create a standard Page with a view for now to be safe, as "Page layouts using schemas" might be a specific subtype or I might miss the exact method name.
    // AND I'll create the view.

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
