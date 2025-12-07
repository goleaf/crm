<?php

declare(strict_types=1);

namespace App\Services\Opportunities;

use App\Enums\CustomFields\OpportunityField;
use App\Models\Opportunity;
use Carbon\Carbon;
use Relaticle\CustomFields\Models\CustomField;

final class OpportunityMetricsService
{
    /**
     * Default probability mappings for the built-in stage options.
     *
     * @var array<string, float>
     */
    private const array STAGE_PROBABILITIES = [
        'Prospecting' => 10.0,
        'Qualification' => 20.0,
        'Needs Analysis' => 30.0,
        'Value Proposition' => 40.0,
        'Id. Decision Makers' => 50.0,
        'Perception Analysis' => 55.0,
        'Proposal/Price Quote' => 65.0,
        'Negotiation/Review' => 80.0,
        'Closed Won' => 100.0,
        'Closed Lost' => 0.0,
    ];

    /**
     * Cache of custom fields keyed by tenant and field code.
     *
     * @var array<string, CustomField|null>
     */
    private array $fieldCache = [];

    public function amount(Opportunity $opportunity): ?float
    {
        $value = $this->value($opportunity, OpportunityField::AMOUNT->value);

        return is_numeric($value) ? (float) $value : null;
    }

    public function probability(Opportunity $opportunity): ?float
    {
        $explicitProbability = $this->value($opportunity, OpportunityField::PROBABILITY->value);
        if (is_numeric($explicitProbability)) {
            return $this->clampPercentage((float) $explicitProbability);
        }

        return $this->probabilityFromStage($opportunity);
    }

    public function weightedAmount(Opportunity $opportunity): ?float
    {
        $amount = $this->amount($opportunity);
        $probability = $this->probability($opportunity);

        if ($amount === null || $probability === null) {
            return null;
        }

        return round($amount * ($probability / 100), 2);
    }

    public function expectedCloseDate(Opportunity $opportunity): ?Carbon
    {
        $value = $this->value($opportunity, OpportunityField::CLOSE_DATE->value);

        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_string($value)) {
            return Carbon::parse($value);
        }

        return null;
    }

    public function salesCycleDays(Opportunity $opportunity): ?int
    {
        $closeDate = $this->expectedCloseDate($opportunity);

        if (! $closeDate instanceof \Carbon\Carbon || $opportunity->created_at === null) {
            return null;
        }

        return $opportunity->created_at->diffInDays($closeDate, absolute: true);
    }

    public function forecastCategory(Opportunity $opportunity): ?string
    {
        $field = $this->field($opportunity, OpportunityField::FORECAST_CATEGORY->value);
        $value = $this->value($opportunity, OpportunityField::FORECAST_CATEGORY->value);

        return $field instanceof \Relaticle\CustomFields\Models\CustomField ? $this->optionLabel($field, $value) : null;
    }

    public function stageLabel(Opportunity $opportunity): ?string
    {
        $field = $this->field($opportunity, OpportunityField::STAGE->value);
        $value = $this->value($opportunity, OpportunityField::STAGE->value);

        return $field instanceof \Relaticle\CustomFields\Models\CustomField ? $this->optionLabel($field, $value) : null;
    }

    /**
     * Resolve a custom field for the opportunity's tenant.
     */
    private function field(Opportunity $opportunity, string $code): ?CustomField
    {
        $tenantId = $opportunity->team_id;
        $cacheKey = "{$tenantId}:{$code}";

        if (! array_key_exists($cacheKey, $this->fieldCache)) {
            $query = CustomField::query()
                ->forEntity(Opportunity::class)
                ->where('code', $code);

            if ($tenantId !== null) {
                $tenantColumn = config('custom-fields.database.column_names.tenant_foreign_key');
                $query->where($tenantColumn, $tenantId);
            }

            $this->fieldCache[$cacheKey] = $query->first();
        }

        return $this->fieldCache[$cacheKey];
    }

    private function value(Opportunity $opportunity, string $code): mixed
    {
        $field = $this->field($opportunity, $code);
        if (! $field instanceof \Relaticle\CustomFields\Models\CustomField) {
            return null;
        }

        $opportunity->loadMissing('customFieldValues.customField.options');

        return $opportunity->getCustomFieldValue($field);
    }

    private function probabilityFromStage(Opportunity $opportunity): ?float
    {
        $field = $this->field($opportunity, OpportunityField::STAGE->value);
        $value = $this->value($opportunity, OpportunityField::STAGE->value);

        if (! $field instanceof \Relaticle\CustomFields\Models\CustomField || $value === null) {
            return null;
        }

        $label = $this->optionLabel($field, $value);

        return self::STAGE_PROBABILITIES[$label] ?? null;
    }

    private function optionLabel(CustomField $field, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $field->loadMissing('options');

        if (is_numeric($value)) {
            $option = $field->options->firstWhere('id', (int) $value);

            return $option?->name;
        }

        return (string) $value;
    }

    private function clampPercentage(float $value): float
    {
        return max(0.0, min(100.0, $value));
    }
}
