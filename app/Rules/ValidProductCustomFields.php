<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidProductCustomFields implements ValidationRule
{
    public function __construct(
        private readonly ?Product $product = null
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('The custom fields must be an array.');
            return;
        }

        // Create a temporary product instance for validation if none provided
        $product = $this->product ?? new Product();
        
        $errors = $product->validateCustomFields($value);
        
        if (!empty($errors)) {
            foreach ($errors as $fieldId => $error) {
                $fail("Custom field validation failed: {$error}");
            }
        }
    }
}