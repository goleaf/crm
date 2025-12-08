<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Process;

final class TranslationStatus extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-language';

    protected string $view = 'filament.pages.translation-status';

    protected static \UnitEnum|string|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Translation Status';

    protected static ?string $title = 'Translation Status';

    public string $output = '';

    public function mount(): void
    {
        $this->checkTranslations();
    }

    public function checkTranslations(): void
    {
        // Run the command and capture output
        // Note: The package command might return exit code 1 if issues found, so we catch that.

        $result = Process::run('php artisan translations:check');

        $this->output = $result->output();

        if (empty($this->output) && ! empty($result->errorOutput())) {
            $this->output = $result->errorOutput();
        }
    }
}
