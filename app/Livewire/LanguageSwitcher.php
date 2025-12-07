<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

final class LanguageSwitcher extends Component
{
    public string $currentLocale;

    public array $availableLocales = [
        'en' => ['name' => 'English', 'flag' => '🇬🇧'],
        'ru' => ['name' => 'Русский', 'flag' => '🇷🇺'],
        'lt' => ['name' => 'Lietuvių', 'flag' => '🇱🇹'],
    ];

    public function mount(): void
    {
        $this->currentLocale = App::getLocale();
    }

    public function switchLanguage(string $locale): void
    {
        if (! array_key_exists($locale, $this->availableLocales)) {
            return;
        }

        Session::put('locale', $locale);
        App::setLocale($locale);
        $this->currentLocale = $locale;

        $this->dispatch('locale-changed', locale: $locale);
        $this->redirect(request()->header('Referer') ?: '/');
    }

    public function render(): View
    {
        return view('livewire.language-switcher');
    }
}
