<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProcessEventType: string implements HasLabel
{
    case EXECUTION_STARTED = 'execution_started';
    case EXECUTION_COMPLETED = 'execution_completed';
    case EXECUTION_FAILED = 'execution_failed';
    case EXECUTION_CANCELLED = 'execution_cancelled';
    case STEP_STARTED = 'step_started';
    case STEP_COMPLETED = 'step_completed';
    case STEP_FAILED = 'step_failed';
    case APPROVAL_REQUESTED = 'approval_requested';
    case APPROVAL_GRANTED = 'approval_granted';
    case APPROVAL_REJECTED = 'approval_rejected';
    case ESCALATION_TRIGGERED = 'escalation_triggered';
    case SLA_BREACHED = 'sla_breached';
    case ROLLBACK_INITIATED = 'rollback_initiated';
    case ROLLBACK_COMPLETED = 'rollback_completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::EXECUTION_STARTED => __('enums.process_event_type.execution_started'),
            self::EXECUTION_COMPLETED => __('enums.process_event_type.execution_completed'),
            self::EXECUTION_FAILED => __('enums.process_event_type.execution_failed'),
            self::EXECUTION_CANCELLED => __('enums.process_event_type.execution_cancelled'),
            self::STEP_STARTED => __('enums.process_event_type.step_started'),
            self::STEP_COMPLETED => __('enums.process_event_type.step_completed'),
            self::STEP_FAILED => __('enums.process_event_type.step_failed'),
            self::APPROVAL_REQUESTED => __('enums.process_event_type.approval_requested'),
            self::APPROVAL_GRANTED => __('enums.process_event_type.approval_granted'),
            self::APPROVAL_REJECTED => __('enums.process_event_type.approval_rejected'),
            self::ESCALATION_TRIGGERED => __('enums.process_event_type.escalation_triggered'),
            self::SLA_BREACHED => __('enums.process_event_type.sla_breached'),
            self::ROLLBACK_INITIATED => __('enums.process_event_type.rollback_initiated'),
            self::ROLLBACK_COMPLETED => __('enums.process_event_type.rollback_completed'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
