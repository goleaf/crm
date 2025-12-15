<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class Validate
{
    /**
     * Run a quick validator and return the validated data or throw.
     *
     * @param array<string, mixed>  $data
     * @param array<string, mixed>  $rules
     * @param array<string, string> $messages
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function against(array $data, array $rules, array $messages = []): array
    {
        return Validator::make($data, $rules, $messages)->after(static function ($validator): void {
            // Hook for fluent usage; no-op by default.
        })->validate();
    }

    /**
     * Determine if the data passes validation without throwing.
     */
    public static function passes(array $data, array $rules): bool
    {
        return Validator::make($data, $rules)->passes();
    }
}
