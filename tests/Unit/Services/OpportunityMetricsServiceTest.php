<?php

declare(strict_types=1);

use App\Enums\CustomFields\OpportunityField;
use App\Enums\CustomFieldType;
use App\Models\Opportunity;
use App\Models\Team;
use App\Services\Opportunities\OpportunityMetricsService;
use Relaticle\CustomFields\Services\TenantContextService;

test('probability prefers explicit field then stage defaults', function (): void {
    $team = Team::factory()->create();
    TenantContextService::setTenantId($team->getKey());

    $stageField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::STAGE->value,
        CustomFieldType::SELECT->value,
        OpportunityField::STAGE->getOptions() ?? [],
        $team
    );

    $probabilityField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::PROBABILITY->value,
        CustomFieldType::NUMBER->value,
        [],
        $team
    );

    $opportunity = Opportunity::factory()->for($team, 'team')->create();

    $stageField->loadMissing('options');
    $negotiationStage = $stageField->options->firstWhere('name', 'Negotiation/Review') ?? $stageField->options->first();
    $opportunity->saveCustomFieldValue($stageField, $negotiationStage->getKey());

    $service = resolve(OpportunityMetricsService::class);

    expect($service->probability($opportunity))->toBe(80.0);

    $opportunity->saveCustomFieldValue($probabilityField, 35);

    expect($service->probability($opportunity))->toBe(35.0);
});

test('weighted amount and sales cycle are derived from amount, probability, and close date', function (): void {
    $team = Team::factory()->create();
    TenantContextService::setTenantId($team->getKey());

    $amountField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::AMOUNT->value,
        CustomFieldType::CURRENCY->value,
        [],
        $team
    );

    $probabilityField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::PROBABILITY->value,
        CustomFieldType::NUMBER->value,
        [],
        $team
    );

    $closeDateField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::CLOSE_DATE->value,
        CustomFieldType::DATE->value,
        [],
        $team
    );

    $forecastField = createCustomFieldFor(
        Opportunity::class,
        OpportunityField::FORECAST_CATEGORY->value,
        CustomFieldType::SELECT->value,
        OpportunityField::FORECAST_CATEGORY->getOptions() ?? [],
        $team
    );

    $opportunity = Opportunity::factory()
        ->for($team, 'team')
        ->create([
            'created_at' => now()->subDays(5),
        ]);

    $forecastField->loadMissing('options');
    $opportunity->saveCustomFieldValue($forecastField, $forecastField->options->firstWhere('name', 'Commit')?->getKey());

    $opportunity->saveCustomFieldValue($amountField, 20000);
    $opportunity->saveCustomFieldValue($probabilityField, 50);
    $opportunity->saveCustomFieldValue($closeDateField, now()->addDays(10));

    $service = resolve(OpportunityMetricsService::class);

    expect($service->weightedAmount($opportunity))->toBe(10000.0)
        ->and($service->salesCycleDays($opportunity))->toBe(15)
        ->and($service->forecastCategory($opportunity))->toBe('Commit');
});
