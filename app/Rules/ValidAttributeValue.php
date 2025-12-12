<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\ProductAttribute;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidAttributeValue implements ValidationRule
{
    public function __construct(
        private readonly ProductAttribute $attribute
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->attribute->isValidValue($value)) {
            $fail("The {$attribute} field must be a valid {$this->attribute->data_type->getLabel()} value.");
        }
    }
}