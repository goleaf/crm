<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

final class LanguageSwitcher extends Component
{
    private const array LOCALE_FLAGS = [
        'en' => '🇬🇧',
        'ru' => '🇷🇺',
        'lt' => '🇱🇹',
    ];

    public string $currentLocale;

    public array $availableLocales = [];

    public function mount(): void
    {
        $supportedLocales = LaravelLocalization::getSupportedLocales();
        $orderedLocales = config('laravellocalization.localesOrder', []);

        if ($orderedLocales === []) {
            $orderedLocales = array_keys($supportedLocales);
        }

        $this->availableLocales = collect($orderedLocales)
            ->filter(fn (string $locale): bool => array_key_exists($locale, $supportedLocales))
            ->mapWithKeys(function (string $locale) use ($supportedLocales): array {
                $label = $supportedLocales[$locale]['native']
                    ?? $supportedLocales[$locale]['name']
                    ?? strtoupper($locale);

                return [
                    $locale => [
                        'name' => $label,
                        'flag' => self::LOCALE_FLAGS[$locale] ?? '🏳️',
                    ],
                ];
            })
            ->all();

        if ($this->availableLocales === []) {
            $this->availableLocales = [
                App::getLocale() => [
                    'name' => App::getLocale(),
                    'flag' => '🏳️',
                ],
            ];
        }

        $this->currentLocale = LaravelLocalization::getCurrentLocale() ?? App::getLocale();
    }

    public function switchLanguage(string $locale): void
    {
        if (! array_key_exists($locale, $this->availableLocales)) {
            return;
        }

        Session::put('locale', $locale);
        LaravelLocalization::setLocale($locale);
        App::setLocale($locale);
        $this->currentLocale = $locale;

        $this->dispatch('locale-changed', locale: $locale);

        $redirectUrl = LaravelLocalization::getLocalizedURL(
            $locale,
            url()->current(),
            [],
            true,
        ) ?: url()->current();

        $queryString = request()->getQueryString();

        if ($queryString !== null) {
            $redirectUrl .= '?' . $queryString;
        }

        $this->redirect($redirectUrl);
    }

    public function render(): View
    {
        return view('livewire.language-switcher');
    }
}
