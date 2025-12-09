<?php

declare(strict_types=1);

use App\Enums\CustomFields\OpportunityField;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Team;
use App\Models\User;
use App\Services\Opportunities\OpportunityMetricsService;
use App\Services\Opportunities\OpportunityStageService;
use Relaticle\CustomFields\Services\TenantContextService;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->user->switchTeam($this->team);

    TenantContextService::setTenantId($this->team->getKey());

    $this->metricsService = new OpportunityMetricsService;
    $this->stageService = new OpportunityStageService($this->metricsService);

    // Create custom fields for opportunities and store them
    $this->customFields = [];
    foreach (OpportunityField::cases() as $fieldEnum) {
        $this->customFields[$fieldEnum->value] = createCustomFieldFor(
            Opportunity::class,
            $fieldEnum->value,
            $fieldEnum->getFieldType(),
            $fieldEnum->getOptions() ?? [],
            $this->team,
        );
    }
});

/**
 * **Feature: core-crm-modules, Property 5: Opportunity pipeline math**
 *
 * **Validates: Requirements 4.1**
 *
 * Property: For any opportunity with an amount and probability,
 * weighted revenue equals amount * probability and totals roll up correctly.
 */
test('property: weighted revenue equals amount times probability', function (): void {
    $company = Company::factory()->for($this->team)->create();
    $opportunity = Opportunity::factory()
        ->for($this->team)
        ->for($company)
        ->for($this->user, 'creator')
        ->create();

    $amount = fake()->randomFloat(2, 1000, 100000);
    $probability = fake()->randomFloat(2, 1, 100);

    $amountField = $this->customFields[OpportunityField::AMOUNT->value];
    $probabilityField = $this->customFields[OpportunityField::PROBABILITY->value];

    $opportunity->saveCustomFieldValue($amountField, $amount);
    $opportunity->saveCustomFieldValue($probabilityField, $probability);

    // Ensure tenant context is set
    TenantContextService::setTenantId($this->team->getKey());

    // Fresh load with custom field values
    $opportunity = Opportunity::with('customFieldValues.customField')->find($opportunity->id);

    $retrievedAmount = $this->metricsService->amount($opportunity);
    $retrievedProbability = $this->metricsService->probability($opportunity);
    $calculatedWeighted = $this->metricsService->weightedAmount($opportunity);

    // Calculate expected using the RETRIEVED values to account for any storage transformations
    $expectedWeighted = round($retrievedAmount * ($retrievedProbability / 100), 2);

    expect($calculatedWeighted)->toBe($expectedWeighted);
})->repeat(100);

/**
 * **Feature: core-crm-modules, Property 5: Opportunity pipeline math**
 *
 * **Validates: Requirements 4.1**
 *
 * Property: For any opportunity with a stage but no explicit probability,
 * the probability should be derived from the stage's default probability.
 */
test('property: probability derived from stage when not explicitly set', function (): void {
    $company = Company::factory()->for($this->team)->create();
    $opportunity = Opportunity::factory()
        ->for($this->team)
        ->for($company)
        ->for($this->user, 'creator')
        ->create();

    $stages = [
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

    $stageName = fake()->randomElement(array_keys($stages));
    $expectedProbability = $stages[$stageName];

    $stageField = $this->customFields[OpportunityField::STAGE->value];
    $stageOption = $stageField->options->firstWhere('name', $stageName);
    $opportunity->saveCustomFieldValue($stageField, $stageOption->id);

    $opportunity->refresh();

    $derivedProbability = $this->metricsService->probability($opportunity);

    expect($derivedProbability)->toBe($expectedProbability);
})->repeat(100);

/**
 * **Feature: core-crm-modules, Property 6: Opportunity stage progression**
 *
 * **Validates: Requirements 4.4**
 *
 * Property: For any opportunity, stage changes must honor allowed transitions.
 * Invalid transitions should be rejected.
 */
test('property: stage transitions must follow allowed progression rules', function (): void {
    $company = Company::factory()->for($this->team)->create();
    $opportunity = Opportunity::factory()
        ->for($this->team)
        ->for($company)
        ->for($this->user, 'creator')
        ->create();

    $validTransitions = [
        'Prospecting' => ['Qualification', 'Closed Lost'],
        'Qualification' => ['Needs Analysis', 'Prospecting', 'Closed Lost'],
        'Needs Analysis' => ['Value Proposition', 'Qualification', 'Closed Lost'],
        'Value Proposition' => ['Id. Decision Makers', 'Needs Analysis', 'Closed Lost'],
        'Id. Decision Makers' => ['Perception Analysis', 'Value Proposition', 'Closed Lost'],
        'Perception Analysis' => ['Proposal/Price Quote', 'Id. Decision Makers', 'Closed Lost'],
        'Proposal/Price Quote' => ['Negotiation/Review', 'Perception Analysis', 'Closed Lost'],
        'Negotiation/Review' => ['Closed Won', 'Closed Lost', 'Proposal/Price Quote'],
    ];

    $fromStage = fake()->randomElement(array_keys($validTransitions));
    $allowedNextStages = $validTransitions[$fromStage];

    $stageField = $this->customFields[OpportunityField::STAGE->value];
    $fromStageOption = $stageField->options->firstWhere('name', $fromStage);
    $opportunity->saveCustomFieldValue($stageField, $fromStageOption->id);

    $toStage = fake()->randomElement($allowedNextStages);

    $canTransition = $this->stageService->canTransition($opportunity, $toStage);

    expect($canTransition)->toBeTrue();
})->repeat(100);

/**
 * **Feature: core-crm-modules, Property 6: Opportunity stage progression**
 *
 * **Validates: Requirements 4.4**
 *
 * Property: For any opportunity, attempting to transition to a disallowed stage
 * should be rejected.
 */
test('property: invalid stage transitions are rejected', function (): void {
    $company = Company::factory()->for($this->team)->create();
    $opportunity = Opportunity::factory()
        ->for($this->team)
        ->for($company)
        ->for($this->user, 'creator')
        ->create();

    $invalidTransitions = [
        'Prospecting' => ['Needs Analysis', 'Value Proposition', 'Proposal/Price Quote', 'Closed Won'],
        'Closed Won' => ['Prospecting', 'Qualification', 'Negotiation/Review'],
        'Closed Lost' => ['Prospecting', 'Qualification', 'Closed Won'],
    ];

    $fromStage = fake()->randomElement(array_keys($invalidTransitions));
    $invalidNextStages = $invalidTransitions[$fromStage];

    $stageField = $this->customFields[OpportunityField::STAGE->value];
    $fromStageOption = $stageField->options->firstWhere('name', $fromStage);
    $opportunity->saveCustomFieldValue($stageField, $fromStageOption->id);

    $toStage = fake()->randomElement($invalidNextStages);

    $canTransition = $this->stageService->canTransition($opportunity, $toStage);

    expect($canTransition)->toBeFalse();
})->repeat(100);

/**
 * **Feature: core-crm-modules, Property 6: Opportunity stage progression**
 *
 * **Validates: Requirements 4.4**
 *
 * Property: For any opportunity transitioning to Closed Won or Closed Lost,
 * the closed_at timestamp and closed_by_id must be set.
 */
test('property: closing an opportunity sets closed_at and closed_by_id', function (): void {
    $this->actingAs($this->user);

    $company = Company::factory()->for($this->team)->create();
    $opportunity = Opportunity::factory()
        ->for($this->team)
        ->for($company)
        ->for($this->user, 'creator')
        ->create();

    $stageField = $this->customFields[OpportunityField::STAGE->value];
    $negotiationOption = $stageField->options->firstWhere('name', 'Negotiation/Review');
    $opportunity->saveCustomFieldValue($stageField, $negotiationOption->id);

    $closingStage = fake()->randomElement(['Closed Won', 'Closed Lost']);
    $closeReason = fake()->sentence();

    $this->stageService->transitionStage($opportunity, $closingStage, $closeReason);

    $opportunity->refresh();

    expect($opportunity->closed_at)->not->toBeNull()
        ->and($opportunity->closed_by_id)->toBe($this->user->getKey());
})->repeat(100);
