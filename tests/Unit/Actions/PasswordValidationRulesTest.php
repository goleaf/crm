<?php

declare(strict_types=1);

use App\Actions\Fortify\PasswordValidationRules;
use Illuminate\Support\Facades\Validator;

it('rejects common passwords via LaraUtilX rule', function (): void {
    $rules = (new class {
        use PasswordValidationRules;

        public function rules(): array
        {
            return $this->passwordRules(
                user: null,
                input: ['email' => 'user@example.com'],
            );
        }
    })->rules();

    $validator = Validator::make([
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], [
        'password' => $rules,
    ]);

    expect($validator->fails())->toBeTrue();
});
