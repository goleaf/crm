<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WorkflowConditionOperator: string implements HasLabel
{
    case EQUALS = 'equals';
    case NOT_EQUALS = 'not_equals';
    case GREATER_THAN = 'greater_than';
    case LESS_THAN = 'less_than';
    case GREATER_THAN_OR_EQUAL = 'greater_than_or_equal';
    case LESS_THAN_OR_EQUAL = 'less_than_or_equal';
    case CONTAINS = 'contains';
    case NOT_CONTAINS = 'not_contains';
    case STARTS_WITH = 'starts_with';
    case ENDS_WITH = 'ends_with';
    case IS_EMPTY = 'is_empty';
    case IS_NOT_EMPTY = 'is_not_empty';
    case IN = 'in';
    case NOT_IN = 'not_in';
    case BETWEEN = 'between';
    case CHANGED = 'changed';
    case NOT_CHANGED = 'not_changed';

    public function getLabel(): string
    {
        return match ($this) {
            self::EQUALS => __('enums.workflow_condition_operator.equals'),
            self::NOT_EQUALS => __('enums.workflow_condition_operator.not_equals'),
            self::GREATER_THAN => __('enums.workflow_condition_operator.greater_than'),
            self::LESS_THAN => __('enums.workflow_condition_operator.less_than'),
            self::GREATER_THAN_OR_EQUAL => __('enums.workflow_condition_operator.greater_than_or_equal'),
            self::LESS_THAN_OR_EQUAL => __('enums.workflow_condition_operator.less_than_or_equal'),
            self::CONTAINS => __('enums.workflow_condition_operator.contains'),
            self::NOT_CONTAINS => __('enums.workflow_condition_operator.not_contains'),
            self::STARTS_WITH => __('enums.workflow_condition_operator.starts_with'),
            self::ENDS_WITH => __('enums.workflow_condition_operator.ends_with'),
            self::IS_EMPTY => __('enums.workflow_condition_operator.is_empty'),
            self::IS_NOT_EMPTY => __('enums.workflow_condition_operator.is_not_empty'),
            self::IN => __('enums.workflow_condition_operator.in'),
            self::NOT_IN => __('enums.workflow_condition_operator.not_in'),
            self::BETWEEN => __('enums.workflow_condition_operator.between'),
            self::CHANGED => __('enums.workflow_condition_operator.changed'),
            self::NOT_CHANGED => __('enums.workflow_condition_operator.not_changed'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
