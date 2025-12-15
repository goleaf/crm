<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Workflow trigger type enumeration.
 *
 * Defines when a workflow should be triggered during the lifecycle of a record.
 * Used by the workflow automation system to determine execution timing.
 */
enum WorkflowTriggerType: string implements HasLabel
{
    /** Trigger when a new record is created */
    case ON_CREATE = 'on_create';

    /** Trigger when an existing record is edited */
    case ON_EDIT = 'on_edit';

    /** Trigger after a record is saved (create or edit) */
    case AFTER_SAVE = 'after_save';

    /** Trigger based on a schedule (cron expression) */
    case SCHEDULED = 'scheduled';

    /**
     * Get the translated label for the trigger type.
     *
     * @return string The localized label for display in UI
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ON_CREATE => __('enums.workflow_trigger_type.on_create'),
            self::ON_EDIT => __('enums.workflow_trigger_type.on_edit'),
            self::AFTER_SAVE => __('enums.workflow_trigger_type.after_save'),
            self::SCHEDULED => __('enums.workflow_trigger_type.scheduled'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
