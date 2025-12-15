<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class MacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        URL::macro('getAppUrl', function (string $path = ''): string {
            $baseUrl = config('app.url');
            $parsed = parse_url((string) $baseUrl);
            $scheme = $parsed['scheme'] ?? 'https';
            $host = $parsed['host'] ?? 'localhost';

            $base = rtrim($scheme . '://' . $host, '/');
            $appBase = $base . '/app';

            $path = ltrim($path, '/');

            return $path === '' ? $appBase : $appBase . '/' . $path;
        });

        URL::macro('getPublicUrl', function (string $path = ''): string {
            $baseUrl = config('app.url');
            $parsed = parse_url((string) $baseUrl);
            $scheme = $parsed['scheme'] ?? 'https';
            $host = $parsed['host'] ?? 'localhost';

            return $scheme . '://' . $host . '/' . ltrim($path, '/');
        });

        \Filament\Forms\Components\Field::macro('precognitive', function (bool $condition = true, ?int $debounce = null): static {
            if (! $condition) {
                return $this;
            }

            if ($debounce !== null) {
                $this->live(debounce: $debounce);
            } else {
                $this->live(onBlur: true);
            }

            return $this->afterStateUpdated(function ($component, $livewire): void {
                $livewire->validateOnly($component->getStatePath());
            });
        });
    }
}
