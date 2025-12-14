<?php

declare(strict_types=1);

namespace App\Models\Studio;

use App\Models\Model;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Layout Definition Model
 *
 * Stores layout configurations for different views (list/detail/edit/search/subpanels)
 * with per-module scoping and team isolation.
 */
final class LayoutDefinition extends Model
{
    protected $fillable = [
        'team_id',
        'module_name',
        'view_type',
        'name',
        'description',
        'components',
        'ordering',
        'visibility_rules',
        'group_overrides',
        'active',
        'system_defined',
    ];

    protected $casts = [
        'components' => 'array',
        'ordering' => 'array',
        'visibility_rules' => 'array',
        'group_overrides' => 'array',
        'active' => 'boolean',
        'system_defined' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope to active layouts only
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function active($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope by module name
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forModule($query, string $moduleName)
    {
        return $query->where('module_name', $moduleName);
    }

    /**
     * Scope by view type
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forViewType($query, string $viewType)
    {
        return $query->where('view_type', $viewType);
    }

    /**
     * Get available view types
     */
    public static function getViewTypes(): array
    {
        return [
            'list' => __('app.labels.list_view'),
            'detail' => __('app.labels.detail_view'),
            'edit' => __('app.labels.edit_view'),
            'search' => __('app.labels.search_view'),
            'subpanel' => __('app.labels.subpanel_view'),
        ];
    }

    /**
     * Get available modules
     */
    public static function getAvailableModules(): array
    {
        return [
            'companies' => __('app.navigation.companies'),
            'people' => __('app.navigation.people'),
            'opportunities' => __('app.navigation.opportunities'),
            'tasks' => __('app.navigation.tasks'),
            'notes' => __('app.navigation.notes'),
            'leads' => __('app.navigation.leads'),
            'cases' => __('app.navigation.cases'),
            'invoices' => __('app.navigation.invoices'),
            'quotes' => __('app.navigation.quotes'),
            'orders' => __('app.navigation.orders'),
        ];
    }
}
