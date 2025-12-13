<?php

declare(strict_types=1);

namespace App\Models\Studio;

use App\Models\Model;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Label Customization Model
 * 
 * Stores custom labels for fields, modules, and UI elements
 */
final class LabelCustomization extends Model
{
    protected $fillable = [
        'team_id',
        'module_name',
        'element_type',
        'element_key',
        'original_label',
        'custom_label',
        'description',
        'locale',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get available element types
     */
    public static function getElementTypes(): array
    {
        return [
            'field' => __('app.labels.field_label'),
            'module' => __('app.labels.module_label'),
            'action' => __('app.labels.action_label'),
            'navigation' => __('app.labels.navigation_label'),
            'tab' => __('app.labels.tab_label'),
            'section' => __('app.labels.section_label'),
        ];
    }

    /**
     * Scope to active customizations only
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
     * Scope by element type
     */
    public function scopeForElementType($query, string $elementType)
    {
        return $query->where('element_type', $elementType);
    }

    /**
     * Scope by locale
     */
    public function scopeForLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Get custom label or fall back to original
     */
    public function getEffectiveLabel(): string
    {
        return $this->custom_label ?: $this->original_label;
    }
}