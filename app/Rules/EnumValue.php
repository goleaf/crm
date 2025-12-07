<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use UnitEnum;

/**
 * Validates that a value is a valid enum value.
 *
 * Usage:
 * 'status' => ['required', new EnumValue(ProjectStatus::class)]
 */
final readonly class EnumValue implements ValidationRule
{
    /**
     * @param  class-string<UnitEnum>  $enumClass
     */
    public function __construct(
        private string $enumClass
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! enum_exists($this->enumClass)) {
            $fail("The {$attribute} enum class does not exist.");

            return;
        }

        $enum = $this->enumClass;

        // Check if it's a backed enum
        if (method_exists($enum, 'tryFrom')) {
            if ($enum::tryFrom($value) === null) {
                $fail($this->message($attribute));
            }

            return;
        }

        // For unit enums, check by name
        $validNames = array_column($enum::cases(), 'name');
        if (! in_array($value, $validNames, true)) {
            $fail($this->message($attribute));
        }
    }

    /**
     * Get the validation error message.
     */
    private function message(string $attribute): string
    {
        $enumName = class_basename($this->enumClass);

        return __('validation.enum', [
            'attribute' => $attribute,
            'enum' => $enumName,
        ]);
    }
}
