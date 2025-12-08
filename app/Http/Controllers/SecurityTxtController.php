<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Arr;

final readonly class SecurityTxtController
{
    public function __invoke(): Response
    {
        if (! config('security.security_txt.enabled', true)) {
            abort(404);
        }

        $contacts = array_filter((array) config('security.security_txt.contacts', []));

        if ($contacts === []) {
            abort(404);
        }

        $lines = [];

        foreach ($contacts as $contact) {
            $lines[] = 'Contact: '.trim((string) $contact);
        }

        if ($expires = config('security.security_txt.expires')) {
            $lines[] = 'Expires: '.trim((string) $expires);
        }

        foreach (['acknowledgments' => 'Acknowledgments', 'policy' => 'Policy', 'hiring' => 'Hiring'] as $key => $label) {
            $value = config("security.security_txt.{$key}");

            if ($value) {
                $lines[] = "{$label}: ".trim((string) $value);
            }
        }

        $languages = trim((string) config('security.security_txt.preferred_languages', ''));

        if ($languages !== '') {
            $lines[] = 'Preferred-Languages: '.$languages;
        }

        $body = implode(PHP_EOL, Arr::where($lines, fn (string $line): bool => $line !== ''));

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
