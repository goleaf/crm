<?php

declare(strict_types=1);

use App\Models\Team;
use Illuminate\Support\Str;
use Relaticle\CustomFields\Enums\CustomFieldWidth;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Services\TenantContextService;

/**
 * @param  array<int, string>  $options
 */
function createCustomFieldFor(string $entity, string $code, string $type, array $options = [], ?Team $team = null): CustomField
{
    $tenantId = $team?->getKey() ?? Team::factory()->create()->getKey();
    TenantContextService::setTenantId($tenantId);

    $section = CustomFieldSection::query()->firstOrCreate(
        [
            'tenant_id' => $tenantId,
            'entity_type' => $entity,
            'code' => 'general',
        ],
        [
            'name' => 'General',
            'type' => 'headless',
            'sort_order' => 1,
            'system_defined' => true,
            'active' => true,
        ]
    );

    $customField = CustomField::query()->create([
        'tenant_id' => $tenantId,
        'custom_field_section_id' => $section->getKey(),
        'code' => $code,
        'name' => Str::headline(str_replace('_', ' ', $code)),
        'type' => $type,
        'entity_type' => $entity,
        'sort_order' => 1,
        'system_defined' => true,
        'active' => true,
        'width' => CustomFieldWidth::_100->value,
    ]);

    foreach ($options as $index => $optionName) {
        CustomFieldOption::query()->create([
            'tenant_id' => $tenantId,
            'custom_field_id' => $customField->getKey(),
            'name' => $optionName,
            'sort_order' => $index + 1,
        ]);
    }

    return $customField->fresh(['options']);
}
