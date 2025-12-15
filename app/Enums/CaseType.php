<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CaseType: string implements HasLabel
{
    case INCIDENT = 'incident';
    case PROBLEM = 'problem';
    case REQUEST = 'request';
    case QUESTION = 'question';

    public function getLabel(): string
    {
        return match ($this) {
            self::INCIDENT => __('enums.case_type.incident'),
            self::PROBLEM => __('enums.case_type.problem'),
            self::REQUEST => __('enums.case_type.request'),
            self::QUESTION => __('enums.case_type.question'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
