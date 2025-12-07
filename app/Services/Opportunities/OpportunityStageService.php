<?php

declare(strict_types=1);

namespace App\Services\Opportunities;

use App\Enums\CustomFields\OpportunityField;
use App\Models\Opportunity;
use Illuminate\Support\Facades\DB;
use Relaticle\CustomFields\Models\CustomField;

final readonly class OpportunityStageService
{
    /**
     * Valid stage transitions mapping.
     * Each stage can transition to specific next stages.
     *
     * @var array<string, array<string>>
     */
    private const array ALLOWED_TRANSITIONS = [
        'Prospecting' => ['Qualification', 'Closed Lost'],
        'Qualification' => ['Needs Analysis', 'Prospecting', 'Closed Lost'],
        'Needs Analysis' => ['Value Proposition', 'Qualification', 'Closed Lost'],
        'Value Proposition' => ['Id. Decision Makers', 'Needs Analysis', 'Closed Lost'],
        'Id. Decision Makers' => ['Perception Analysis', 'Value Proposition', 'Closed Lost'],
        'Perception Analysis' => ['Proposal/Price Quote', 'Id. Decision Makers', 'Closed Lost'],
        'Proposal/Price Quote' => ['Negotiation/Review', 'Perception Analysis', 'Closed Lost'],
        'Negotiation/Review' => ['Closed Won', 'Closed Lost', 'Proposal/Price Quote'],
        'Closed Won' => [],
        'Closed Lost' => [],
    ];

    public function __construct(
        private OpportunityMetricsService $metricsService
    ) {}

    /**
     * Check if a stage transition is allowed.
     */
    public function canTransition(Opportunity $opportunity, string $toStage): bool
    {
        $currentStage = $this->metricsService->stageLabel($opportunity);

        if ($currentStage === null) {
            return true;
        }

        if ($currentStage === $toStage) {
            return true;
        }

        $allowedStages = self::ALLOWED_TRANSITIONS[$currentStage] ?? [];

        return in_array($toStage, $allowedStages, true);
    }

    /**
     * Transition opportunity to a new stage with validation.
     *
     * @throws \InvalidArgumentException if transition is not allowed
     */
    public function transitionStage(Opportunity $opportunity, string $toStage, ?string $closeReason = null): void
    {
        $currentStage = $this->metricsService->stageLabel($opportunity);

        if (! $this->canTransition($opportunity, $toStage)) {
            throw new \InvalidArgumentException(
                "Cannot transition from '{$currentStage}' to '{$toStage}'"
            );
        }

        DB::transaction(function () use ($opportunity, $toStage, $closeReason): void {
            $stageField = $this->getStageField($opportunity);
            if (! $stageField instanceof \Relaticle\CustomFields\Models\CustomField) {
                throw new \RuntimeException('Stage field not found for opportunity');
            }

            $stageOption = $stageField->options->firstWhere('name', $toStage);
            if ($stageOption === null) {
                throw new \InvalidArgumentException("Invalid stage: {$toStage}");
            }

            $opportunity->setCustomFieldValue($stageField, $stageOption->id);

            if (in_array($toStage, ['Closed Won', 'Closed Lost'], true)) {
                $opportunity->closed_at = now();
                $opportunity->closed_by_id = auth()->id();

                if ($closeReason !== null) {
                    $outcomeField = $this->getOutcomeNotesField($opportunity);
                    if ($outcomeField instanceof \Relaticle\CustomFields\Models\CustomField) {
                        $opportunity->setCustomFieldValue($outcomeField, $closeReason);
                    }
                }
            }

            $opportunity->save();
        });
    }

    /**
     * Get allowed next stages for an opportunity.
     *
     * @return array<string>
     */
    public function getAllowedNextStages(Opportunity $opportunity): array
    {
        $currentStage = $this->metricsService->stageLabel($opportunity);

        if ($currentStage === null) {
            return array_keys(self::ALLOWED_TRANSITIONS);
        }

        return self::ALLOWED_TRANSITIONS[$currentStage] ?? [];
    }

    private function getStageField(Opportunity $opportunity): ?CustomField
    {
        return CustomField::query()
            ->forEntity(Opportunity::class)
            ->where('code', OpportunityField::STAGE->value)
            ->where(config('custom-fields.database.column_names.tenant_foreign_key'), $opportunity->team_id)
            ->first();
    }

    private function getOutcomeNotesField(Opportunity $opportunity): ?CustomField
    {
        return CustomField::query()
            ->forEntity(Opportunity::class)
            ->where('code', OpportunityField::OUTCOME_NOTES->value)
            ->where(config('custom-fields.database.column_names.tenant_foreign_key'), $opportunity->team_id)
            ->first();
    }
}
