<?php

declare(strict_types=1);

namespace App\Models\Studio;

use App\Models\Model;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Field Dependency Model
 * 
 * Manages field dependencies and conditional visibility rules
 */
final class FieldDependency extends Model
{
    protected $fillable = [
        'team_id',
        'module_name',
        'source_field_code',
        'target_field_code',
        'dependency_type',
        'condition_operator',
        'condition_value',
        'action_type',
        'action_config',
        'active',
    ];

    protected $casts = [
        'condition_value' => 'array',
        'action_config' => 'array',
        'active' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get available dependency types
     */
    public static function getDependencyTypes(): array
    {
        return [
            'visibility' => __('app.labels.visibility_dependency'),
            'required' => __('app.labels.required_dependency'),
            'options' => __('app.labels.options_dependency'),
            'validation' => __('app.labels.validation_dependency'),
        ];
    }

    /**
     * Get available condition operators
     */
    public static function getConditionOperators(): array
    {
        return [
            'equals' => __('app.labels.equals'),
            'not_equals' => __('app.labels.not_equals'),
            'contains' => __('app.labels.contains'),
            'not_contains' => __('app.labels.not_contains'),
            'in' => __('app.labels.in'),
            'not_in' => __('app.labels.not_in'),
            'greater_than' => __('app.labels.greater_than'),
            'less_than' => __('app.labels.less_than'),
            'is_empty' => __('app.labels.is_empty'),
            'is_not_empty' => __('app.labels.is_not_empty'),
        ];
    }

    /**
     * Get available action types
     */
    public static function getActionTypes(): array
    {
        return [
            'show' => __('app.labels.show_field'),
            'hide' => __('app.labels.hide_field'),
            'require' => __('app.labels.require_field'),
            'optional' => __('app.labels.make_optional'),
            'filter_options' => __('app.labels.filter_options'),
            'set_value' => __('app.labels.set_value'),
        ];
    }

    /**
     * Scope to active dependencies only
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope by module name
     */
    public function scopeForModule($query, string $moduleName)
    {
        return $query->where('module_name', $moduleName);
    }

    /**
     * Scope by source field
     */
    public function scopeForSourceField($query, string $fieldCode)
    {
        return $query->where('source_field_code', $fieldCode);
    }

    /**
     * Scope by target field
     */
    public function scopeForTargetField($query, string $fieldCode)
    {
        return $query->where('target_field_code', $fieldCode);
    }
}