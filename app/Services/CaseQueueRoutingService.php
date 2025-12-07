<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SupportCase;
use Illuminate\Support\Facades\Config;

final class CaseQueueRoutingService
{
    /**
     * Determine the appropriate queue for a case based on routing rules.
     */
    public function determineQueue(SupportCase $case): string
    {
        if (! Config::get('cases.queue_routing.enabled', true)) {
            return Config::get('cases.queue_routing.default_queue', 'general');
        }

        $rules = Config::get('cases.queue_routing.rules', []);

        foreach ($rules as $rule) {
            if ($this->matchesRule($case, $rule)) {
                return $rule['queue'];
            }
        }

        return Config::get('cases.queue_routing.default_queue', 'general');
    }

    /**
     * Assign a case to the appropriate queue and team.
     */
    public function assignQueue(SupportCase $case): void
    {
        $queue = $this->determineQueue($case);
        $teamId = $this->determineTeam($case);

        $case->update([
            'queue' => $queue,
            'assigned_team_id' => $teamId,
        ]);
    }

    /**
     * Determine the appropriate team for a case based on routing rules.
     */
    public function determineTeam(SupportCase $case): ?int
    {
        if (! Config::get('cases.queue_routing.enabled', true)) {
            return null;
        }

        $rules = Config::get('cases.queue_routing.rules', []);

        foreach ($rules as $rule) {
            if ($this->matchesRule($case, $rule) && isset($rule['team_id'])) {
                return $rule['team_id'];
            }
        }

        return null;
    }

    /**
     * Check if a case matches a routing rule.
     *
     * @param  array<string, mixed>  $rule
     */
    private function matchesRule(SupportCase $case, array $rule): bool
    {
        $conditions = $rule['conditions'] ?? [];

        foreach ($conditions as $field => $values) {
            $caseValue = $case->{$field};

            // Handle enum values
            if (is_object($caseValue) && method_exists($caseValue, 'value')) {
                $caseValue = $caseValue->value;
            }

            if (! in_array($caseValue, $values, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all available queues from configuration.
     *
     * @return array<string>
     */
    public function getAvailableQueues(): array
    {
        $rules = Config::get('cases.queue_routing.rules', []);
        $queues = array_column($rules, 'queue');
        $queues[] = Config::get('cases.queue_routing.default_queue', 'general');

        return array_unique($queues);
    }
}
