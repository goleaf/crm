<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\CustomFieldType;

enum OpportunityField: string
{
    use CustomFieldTrait;

    case AMOUNT = 'amount';
    case CLOSE_DATE = 'close_date';
    case STAGE = 'stage';
    case PROBABILITY = 'probability';
    case FORECAST_CATEGORY = 'forecast_category';
    case NEXT_STEPS = 'next_steps';
    case COMPETITORS = 'competitors';
    case RELATED_QUOTES = 'related_quotes';
    case OUTCOME_NOTES = 'outcome_notes';

    /**
     * @return string[]|null
     */
    public function getOptions(): ?array
    {
        return match ($this) {
            self::STAGE => [
                'Prospecting',
                'Qualification',
                'Needs Analysis',
                'Value Proposition',
                'Id. Decision Makers',
                'Perception Analysis',
                'Proposal/Price Quote',
                'Negotiation/Review',
                'Closed Won',
                'Closed Lost',
            ],
            self::FORECAST_CATEGORY => [
                'Pipeline',
                'Best Case',
                'Commit',
                'Closed',
            ],
            default => null,
        };
    }

    public function getFieldType(): string
    {
        return match ($this) {
            self::AMOUNT => CustomFieldType::CURRENCY->value,
            self::CLOSE_DATE => CustomFieldType::DATE->value,
            self::STAGE => CustomFieldType::SELECT->value,
            self::PROBABILITY => CustomFieldType::NUMBER->value,
            self::FORECAST_CATEGORY => CustomFieldType::SELECT->value,
            self::NEXT_STEPS => CustomFieldType::TEXTAREA->value,
            self::COMPETITORS => CustomFieldType::TAGS_INPUT->value,
            self::RELATED_QUOTES => CustomFieldType::TEXTAREA->value,
            self::OUTCOME_NOTES => CustomFieldType::TEXTAREA->value,
        };
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::AMOUNT => __('enums.opportunity_field.amount'),
            self::CLOSE_DATE => __('enums.opportunity_field.close_date'),
            self::STAGE => __('enums.opportunity_field.stage'),
            self::PROBABILITY => __('enums.opportunity_field.probability'),
            self::FORECAST_CATEGORY => __('enums.opportunity_field.forecast_category'),
            self::NEXT_STEPS => __('enums.opportunity_field.next_steps'),
            self::COMPETITORS => __('enums.opportunity_field.competitors'),
            self::RELATED_QUOTES => __('enums.opportunity_field.related_quotes'),
            self::OUTCOME_NOTES => __('enums.opportunity_field.outcome_notes'),
        };
    }

    public function isListToggleableHidden(): bool
    {
        return match ($this) {
            self::AMOUNT,
            self::CLOSE_DATE,
            self::STAGE,
            self::PROBABILITY,
            self::FORECAST_CATEGORY => false,
            default => true,
        };
    }

    public function getWidth(): \Relaticle\CustomFields\Enums\CustomFieldWidth
    {
        return match ($this) {
            self::PROBABILITY,
            self::FORECAST_CATEGORY => \Relaticle\CustomFields\Enums\CustomFieldWidth::_50,
            default => \Relaticle\CustomFields\Enums\CustomFieldWidth::_100,
        };
    }

    /**
     * Get color mapping for select field options
     *
     * 2024 Sophisticated Sales Journey - A unique emotional progression through the
     * pipeline using earth-inspired, professional colors that tell the story of
     * building energy, trust, and momentum from first contact to final outcome.
     * Based on latest color psychology and 2024 design sophistication trends.
     *
     * @return array<int|string, string>|null Array of option => color mappings or null if not applicable
     */
    public function getOptionColors(): ?array
    {
        return match ($this) {
            self::STAGE => [
                'Prospecting' => '#a5b4fc',           // Misty Dawn - Soft exploration of possibilities
                'Qualification' => '#1e40af',         // Ocean Depth - Deep analytical thinking
                'Needs Analysis' => '#0d9488',        // Teal Understanding - Empathy & insight
                'Value Proposition' => '#eab308',     // Golden Clarity - Bright ideas & value
                'Id. Decision Makers' => '#7c3aed',   // Royal Authority - Power & influence
                'Perception Analysis' => '#f59e0b',   // Warm Amber - Comfortable evaluation
                'Proposal/Price Quote' => '#1e293b',  // Professional Navy - Serious business
                'Negotiation/Review' => '#f97316',    // Electric Coral - Dynamic energy
                'Closed Won' => '#059669',            // Victory Emerald - Success celebration
                'Closed Lost' => '#6b7280',           // Silver Acceptance - Respectful closure
            ],
            self::FORECAST_CATEGORY => [
                'Pipeline' => '#0ea5e9',
                'Best Case' => '#22c55e',
                'Commit' => '#f59e0b',
                'Closed' => '#94a3b8',
            ],
            default => null,
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::PROBABILITY => __('enums.opportunity_field.probability_description'),
            self::FORECAST_CATEGORY => __('enums.opportunity_field.forecast_category_description'),
            self::NEXT_STEPS => __('enums.opportunity_field.next_steps_description'),
            self::COMPETITORS => __('enums.opportunity_field.competitors_description'),
            self::RELATED_QUOTES => __('enums.opportunity_field.related_quotes_description'),
            self::OUTCOME_NOTES => __('enums.opportunity_field.outcome_notes_description'),
            default => null,
        };
    }
}
