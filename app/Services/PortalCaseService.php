<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CaseChannel;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Enums\CreationSource;
use App\Models\People;
use App\Models\SupportCase;
use App\Models\Team;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

final readonly class PortalCaseService
{
    public function __construct(
        private CaseQueueRoutingService $queueRoutingService,
        private CaseSlaService $slaService,
    ) {}

    /**
     * Create a case from portal submission.
     *
     * @param array<string, mixed> $data
     */
    public function createFromPortal(array $data, People $contact, Team $team): SupportCase
    {
        if (! Config::get('cases.portal.enabled', true)) {
            throw new \RuntimeException('Portal case submission is not enabled');
        }

        $case = new SupportCase([
            'team_id' => $team->id,
            'case_number' => $this->generateCaseNumber(),
            'subject' => $data['subject'],
            'description' => $data['description'] ?? '',
            'status' => CaseStatus::NEW,
            'priority' => isset($data['priority']) ? CasePriority::from($data['priority']) : CasePriority::from(Config::get('cases.portal.default_priority', CasePriority::P3->value)),
            'type' => isset($data['type']) ? CaseType::from($data['type']) : CaseType::QUESTION,
            'channel' => CaseChannel::PORTAL,
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'portal_visible' => Config::get('cases.portal.auto_visible', true),
            'customer_portal_url' => $this->generatePortalUrl($team),
            'creation_source' => CreationSource::PORTAL,
        ]);

        $case->save();

        // Set SLA due date
        $slaDueDate = $this->slaService->calculateSlaDueDate($case);
        if ($slaDueDate instanceof \Illuminate\Support\Carbon) {
            $case->update(['sla_due_at' => $slaDueDate]);
        }

        // Assign to queue
        $this->queueRoutingService->assignQueue($case);

        return $case;
    }

    /**
     * Get cases visible to a contact in the portal.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, SupportCase>
     */
    public function getPortalCases(People $contact): \Illuminate\Database\Eloquent\Collection
    {
        return SupportCase::where('contact_id', $contact->id)
            ->where('portal_visible', true)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Update portal visibility for a case.
     */
    public function updatePortalVisibility(SupportCase $case, bool $visible): void
    {
        $case->update(['portal_visible' => $visible]);
    }

    /**
     * Generate a unique case number.
     */
    private function generateCaseNumber(): string
    {
        $prefix = 'CASE';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Generate portal URL for a case.
     */
    private function generatePortalUrl(Team $team): string
    {
        // TODO: Implement actual portal URL generation
        return url("/portal/{$team->id}/cases");
    }
}
