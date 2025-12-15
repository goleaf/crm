<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\CustomFieldType;

/**
 * People custom field codes
 */
enum PeopleField: string
{
    use CustomFieldTrait;

    case EMAILS = 'emails';
    case PHONE_NUMBER = 'phone_number';
    case JOB_TITLE = 'job_title';
    case LINKEDIN = 'linkedin';

    public function getFieldType(): string
    {
        return match ($this) {
            self::EMAILS => CustomFieldType::TAGS_INPUT->value,
            self::PHONE_NUMBER => CustomFieldType::TAGS_INPUT->value,
            self::JOB_TITLE => CustomFieldType::TEXT->value,
            self::LINKEDIN => CustomFieldType::LINK->value,
        };
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::EMAILS => __('enums.people_field.emails'),
            self::PHONE_NUMBER => __('enums.people_field.phone_number'),
            self::JOB_TITLE => __('enums.people_field.job_title'),
            self::LINKEDIN => __('enums.people_field.linkedin'),
        };
    }

    public function isListToggleableHidden(): bool
    {
        return match ($this) {
            self::JOB_TITLE, self::EMAILS => false,
            default => true,
        };
    }
}
