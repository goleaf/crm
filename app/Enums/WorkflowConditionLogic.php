<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WorkflowConditionLogic: string implements HasLabel
{
    case AND = 'and';
    case OR = 'or';

    public function getLabel(): string
    {
        return match ($this) {
            self::AND => __('enums.workflow_condition_logic.and'),
            self::OR => __('enums.workflow_condition_logic.or'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
