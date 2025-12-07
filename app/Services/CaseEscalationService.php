<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SupportCase;
use Illuminate\Support\Facades\Config;

final class CaseEscalationService
{
    /**
     * Check if a case should be escalated based on breach duration.
     */
    public function shouldEscalate(SupportCase $case): bool
    {
        if (! Config::get('cases.escalation.enabled', true)) {
            return false;
        }

        if (! $case->sla_breached || $case->sla_breach_at === null) {
            return false;
        }

        if ($case->resolved_at !== null) {
            return false;
        }

        $breachDuration = $case->sla_breach_at->diffInMinutes(now());
        $nextLevel = $case->escalation_level + 1;
        $escalationLevels = Config::get('cases.escalation.levels', []);

        if (! isset($escalationLevels[$nextLevel])) {
            return false;
        }

        $threshold = $escalationLevels[$nextLevel]['threshold_minutes'] ?? 0;

        return $breachDuration >= $threshold;
    }

    /**
     * Escalate a case to the next level.
     */
    public function escalate(SupportCase $case): void
    {
        $nextLevel = $case->escalation_level + 1;
        $escalationLevels = Config::get('cases.escalation.levels', []);

        if (! isset($escalationLevels[$nextLevel])) {
            return;
        }

        $case->update([
            'escalation_level' => $nextLevel,
            'escalated_at' => now(),
        ]);

        // Notify relevant roles
        $this->notifyEscalation();
    }

    /**
     * Process escalations for all eligible cases.
     */
    public function processEscalations(): int
    {
        $cases = SupportCase::query()
            ->where('sla_breached', true)
            ->whereNull('resolved_at')
            ->whereNotNull('sla_breach_at')
            ->get();

        $escalatedCount = 0;

        foreach ($cases as $case) {
            if ($this->shouldEscalate($case)) {
                $this->escalate($case);
                $escalatedCount++;
            }
        }

        return $escalatedCount;
    }

    /**
     * Notify relevant roles about case escalation.
     */
    private function notifyEscalation(): void
    {
        // TODO: Implement notification logic
        // This would typically send notifications to users with the specified roles
        // For now, this is a placeholder for the notification system
    }

    /**
     * Get the current escalation level configuration for a case.
     *
     * @return array<string, mixed>|null
     */
    public function getEscalationLevelConfig(SupportCase $case): ?array
    {
        $escalationLevels = Config::get('cases.escalation.levels', []);

        return $escalationLevels[$case->escalation_level] ?? null;
    }
}
