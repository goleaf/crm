<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorkflowConditionLogic;
use App\Enums\WorkflowTriggerType;

/**
 * @property WorkflowTriggerType|null              $trigger_type
 * @property string|null                           $target_model
 * @property array<int, array<string, mixed>>|null $conditions
 * @property WorkflowConditionLogic                $condition_logic
 * @property bool                                  $allow_repeated_runs
 * @property int|null                              $max_runs_per_record
 * @property array<string, mixed>|null             $schedule_config
 * @property bool                                  $test_mode
 * @property bool                                  $enable_logging
 * @property string                                $log_level
 */
final class WorkflowDefinition extends ProcessDefinition
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'creator_id',
        'name',
        'slug',
        'description',
        'status',
        'version',
        'steps',
        'business_rules',
        'event_triggers',
        'sla_config',
        'escalation_rules',
        'metadata',
        'documentation',
        'template_id',
        'trigger_type',
        'target_model',
        'conditions',
        'condition_logic',
        'allow_repeated_runs',
        'max_runs_per_record',
        'schedule_config',
        'test_mode',
        'enable_logging',
        'log_level',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'trigger_type' => WorkflowTriggerType::class,
            'conditions' => 'array',
            'condition_logic' => WorkflowConditionLogic::class,
            'allow_repeated_runs' => 'boolean',
            'max_runs_per_record' => 'integer',
            'schedule_config' => 'array',
            'test_mode' => 'boolean',
            'enable_logging' => 'boolean',
        ];
    }

    /**
     * Check if workflow should trigger for the given model and event.
     */
    public function shouldTrigger(string $event, object $model): bool
    {
        // Check if workflow is active
        if ($this->status !== \App\Enums\ProcessStatus::ACTIVE) {
            return false;
        }

        // Check if test mode is enabled
        if ($this->test_mode) {
            return false;
        }

        // Check if trigger type matches event
        if (! $this->matchesTriggerType($event)) {
            return false;
        }

        // Check if target model matches
        if ($this->target_model && $model::class !== $this->target_model) {
            return false;
        }

        // Evaluate conditions
        return $this->evaluateConditions($model);
    }

    /**
     * Check if the event matches the trigger type.
     */
    private function matchesTriggerType(string $event): bool
    {
        if (! $this->trigger_type) {
            return false;
        }

        return match ($this->trigger_type) {
            WorkflowTriggerType::ON_CREATE => $event === 'created',
            WorkflowTriggerType::ON_EDIT => $event === 'updated',
            WorkflowTriggerType::AFTER_SAVE => in_array($event, ['created', 'updated'], true),
            WorkflowTriggerType::SCHEDULED => $event === 'scheduled',
        };
    }

    /**
     * Evaluate workflow conditions against the model.
     */
    public function evaluateConditions(object $model): bool
    {
        if (! $this->conditions || count($this->conditions) === 0) {
            return true;
        }

        $results = [];
        foreach ($this->conditions as $condition) {
            $results[] = $this->evaluateCondition($condition, $model);
        }

        return $this->condition_logic === WorkflowConditionLogic::AND
            ? ! in_array(false, $results, true)
            : in_array(true, $results, true);
    }

    /**
     * Evaluate a single condition.
     *
     * @param array<string, mixed> $condition
     */
    private function evaluateCondition(array $condition, object $model): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;

        if (! $field || ! $operator) {
            return false;
        }

        // Get the field value from the model
        $fieldValue = data_get($model, $field);

        // Evaluate based on operator
        return match ($operator) {
            'equals' => $fieldValue === $value,
            'not_equals' => $fieldValue !== $value,
            'greater_than' => $fieldValue > $value,
            'less_than' => $fieldValue < $value,
            'greater_than_or_equal' => $fieldValue >= $value,
            'less_than_or_equal' => $fieldValue <= $value,
            'contains' => is_string($fieldValue) && str_contains($fieldValue, (string) $value),
            'not_contains' => is_string($fieldValue) && ! str_contains($fieldValue, (string) $value),
            'starts_with' => is_string($fieldValue) && str_starts_with($fieldValue, (string) $value),
            'ends_with' => is_string($fieldValue) && str_ends_with($fieldValue, (string) $value),
            'is_empty' => empty($fieldValue),
            'is_not_empty' => ! empty($fieldValue),
            'in' => is_array($value) && in_array($fieldValue, $value, true),
            'not_in' => is_array($value) && ! in_array($fieldValue, $value, true),
            'between' => is_array($value) && count($value) === 2 && $fieldValue >= $value[0] && $fieldValue <= $value[1],
            'changed' => $model instanceof \Illuminate\Database\Eloquent\Model && $model->wasChanged($field),
            'not_changed' => $model instanceof \Illuminate\Database\Eloquent\Model && ! $model->wasChanged($field),
            default => false,
        };
    }

    /**
     * Check if workflow can run again for the given record.
     */
    public function canRunAgain(int $recordId): bool
    {
        if (! $this->allow_repeated_runs) {
            return false;
        }

        if ($this->max_runs_per_record === null) {
            return true;
        }

        $runCount = $this->executions()
            ->where('context_data->record_id', $recordId)
            ->count();

        return $runCount < $this->max_runs_per_record;
    }
}
