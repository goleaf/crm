<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LeadAssignmentStrategy: string implements HasLabel
{
    case MANUAL = 'manual';
    case ROUND_ROBIN = 'round_robin';
    case TERRITORY = 'territory';
    case WEIGHTED = 'weighted';
    case RULE_BASED = 'rule_based';

    public function getLabel(): string
    {
        return match ($this) {
            self::MANUAL => __('enums.lead_assignment_strategy.manual'),
            self::ROUND_ROBIN => __('enums.lead_assignment_strategy.round_robin'),
            self::TERRITORY => __('enums.lead_assignment_strategy.territory'),
            self::WEIGHTED => __('enums.lead_assignment_strategy.weighted'),
            self::RULE_BASED => __('enums.lead_assignment_strategy.rule_based'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $assignment) {
            $options[$assignment->value] = $assignment->getLabel();
        }

        return $options;
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
