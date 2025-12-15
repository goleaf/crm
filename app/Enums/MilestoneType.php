<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasLabel;

enum MilestoneType: string implements HasLabel
{
    use EnumHelpers;

    case PHASE_COMPLETION = 'phase_completion';
    case DELIVERABLE = 'deliverable';
    case REVIEW = 'review';
    case APPROVAL = 'approval';
    case EXTERNAL_DEPENDENCY = 'external_dependency';

    public function getLabel(): string
    {
        return match ($this) {
            self::PHASE_COMPLETION => __('enums.milestone_type.phase_completion'),
            self::DELIVERABLE => __('enums.milestone_type.deliverable'),
            self::REVIEW => __('enums.milestone_type.review'),
            self::APPROVAL => __('enums.milestone_type.approval'),
            self::EXTERNAL_DEPENDENCY => __('enums.milestone_type.external_dependency'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}

