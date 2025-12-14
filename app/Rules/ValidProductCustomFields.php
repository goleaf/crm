<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class ValidProductCustomFields implements ValidationRule
{
    public function __construct(
        private ?Product $product = null,
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('The custom fields must be an array.');

            return;
        }

        // Create a temporary product instance for validation if none provided
        $product = $this->product ?? new Product;

        $errors = $product->validateCustomFields($value);

        foreach ($errors as $error) {
            $fail("Custom field validation failed: {$error}");
        }
    }
}
