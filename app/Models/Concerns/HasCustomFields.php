<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Relaticle\CustomFields\Models\Contracts\HasCustomFields as CustomFieldsContract;
use Relaticle\CustomFields\Models\CustomField;

/**
 * Lightweight bridge to the CustomFields package for models that are not using the base trait.
 */
trait HasCustomFields
{
    public function customFieldValue(CustomField $field): mixed
    {
        if (! $this instanceof CustomFieldsContract) {
            return null;
        }

        return $this->getCustomFieldValue($field);
    }

    public function setCustomFieldValue(CustomField $field, mixed $value): void
    {
        if (! $this instanceof CustomFieldsContract) {
            return;
        }

        $this->saveCustomFieldValue($field, $value);
    }
}
