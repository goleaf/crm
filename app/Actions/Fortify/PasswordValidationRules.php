<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use LaraUtilX\Rules\RejectCommonPasswords;
use Ziming\LaravelZxcvbn\Rules\ZxcvbnRule;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @param array<string, mixed> $input
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(?User $user = null, array $input = [], bool $requiresConfirmation = true): array
    {
        $rules = [
            'required',
            'string',
            Password::default(),
            new ZxcvbnRule($this->passwordUserInputs($user, $input)),
            new RejectCommonPasswords,
        ];

        if ($requiresConfirmation) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<int, string>
     */
    private function passwordUserInputs(?User $user, array $input): array
    {
        $inputs = array_filter([
            $user?->email,
            $user?->name,
            $input['email'] ?? null,
            $input['name'] ?? null,
        ], filled(...));

        return array_values($inputs);
    }
}
