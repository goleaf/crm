<?php

declare(strict_types=1);

use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

it('builds localized urls for translated slugs', function (): void {
    config()->set('app.locale', 'en');
    config()->set('app.fallback_locale', 'en');

    expect(LaravelLocalization::getLocalizedURL('ru', '/terms-of-service'))
        ->toBe(url('/ru/usloviya-obsluzhivaniya'))
        ->and(LaravelLocalization::getLocalizedURL('en', '/terms-of-service'))
        ->toBe(url('/terms-of-service'));
});

it('maps named routes to translated paths when generating localized urls', function (): void {
    $policyPath = route('policy.show', absolute: false);
    $termsPath = route('terms.show', absolute: false);

    expect(LaravelLocalization::getLocalizedURL('lt', $policyPath))->toBe(url('/lt/privatumo-politika'))
        ->and(LaravelLocalization::getLocalizedURL('lt', $termsPath))->toBe(url('/lt/paslaugu-taisykles'))
        ->and(LaravelLocalization::getLocalizedURL('en', $termsPath))->toBe(url('/terms-of-service'));
});
