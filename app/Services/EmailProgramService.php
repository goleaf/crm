<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BounceType;
use App\Enums\EmailProgramStatus;
use App\Enums\EmailSendStatus;
use App\Models\EmailProgram;
use App\Models\EmailProgramBounce;
use App\Models\EmailProgramRecipient;
use App\Models\EmailProgramStep;
use App\Models\EmailProgramUnsubscribe;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class EmailProgramService
{
    /**
     * Schedule recipients for an email program based on audience filters
     */
    public function scheduleRecipients(EmailProgram $program): int
    {
        $recipients = $this->buildAudienceQuery($program)->get();
        $scheduled = 0;

        foreach ($recipients as $recipient) {
            if ($this->isUnsubscribed($program->team_id, $recipient->email)) {
                continue;
            }

            if ($this->hasHardBounce($recipient->email)) {
                continue;
            }

            $this->createRecipient($program, $recipient);
            $scheduled++;
        }

        $program->update([
            'total_recipients' => $scheduled,
            'estimated_audience_size' => $scheduled,
        ]);

        return $scheduled;
    }

    /**
     * Process scheduled sends for a given time window
     */
    public function processPendingSends(Carbon $now): int
    {
        $recipients = EmailProgramRecipient::query()
            ->where('status', EmailSendStatus::PENDING)
            ->where('scheduled_send_at', '<=', $now)
            ->whereHas('emailProgram', function (Builder $query): void {
                $query->where('status', EmailProgramStatus::ACTIVE);
            })
            ->with(['emailProgram', 'emailProgramStep'])
            ->get();

        $processed = 0;

        foreach ($recipients as $recipient) {
            if ($this->shouldThrottle($recipient->emailProgram, $now)) {
                continue;
            }

            if ($this->isInQuietHours($recipient->emailProgram, $now)) {
                $this->rescheduleAfterQuietHours($recipient, $now);

                continue;
            }

            if ($this->evaluateConditionalSendRules($recipient)) {
                $this->sendEmail($recipient);
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Handle email bounce notification
     */
    public function handleBounce(
        string $email,
        string $bounceType,
        ?string $reason = null,
        ?string $diagnosticCode = null,
        ?array $rawMessage = null
    ): void {
        $recipient = EmailProgramRecipient::query()
            ->where('email', $email)
            ->whereIn('status', [EmailSendStatus::SENT, EmailSendStatus::QUEUED])
            ->latest()
            ->first();

        if (! $recipient) {
            return;
        }

        EmailProgramBounce::create([
            'email_program_id' => $recipient->email_program_id,
            'email_program_recipient_id' => $recipient->id,
            'email' => $email,
            'bounce_type' => $bounceType,
            'bounce_reason' => $reason,
            'diagnostic_code' => $diagnosticCode,
            'raw_message' => $rawMessage,
        ]);

        $recipient->update([
            'status' => EmailSendStatus::BOUNCED,
            'bounced_at' => now(),
            'bounce_type' => $bounceType,
            'bounce_reason' => $reason,
        ]);

        $recipient->emailProgram->increment('total_bounced');
        $recipient->emailProgramStep?->increment('bounced_count');
    }

    /**
     * Handle unsubscribe request
     */
    public function handleUnsubscribe(
        int $teamId,
        string $email,
        ?int $programId = null,
        ?string $reason = null,
        ?string $feedback = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        EmailProgramUnsubscribe::updateOrCreate(
            ['team_id' => $teamId, 'email' => $email],
            [
                'email_program_id' => $programId,
                'reason' => $reason,
                'feedback' => $feedback,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]
        );

        // Update all pending recipients for this email
        EmailProgramRecipient::query()
            ->whereHas('emailProgram', fn (Builder $q) => $q->where('team_id', $teamId))
            ->where('email', $email)
            ->where('status', EmailSendStatus::PENDING)
            ->update([
                'status' => EmailSendStatus::UNSUBSCRIBED,
                'unsubscribed_at' => now(),
            ]);
    }

    /**
     * Track email open
     */
    public function trackOpen(int $recipientId): void
    {
        $recipient = EmailProgramRecipient::find($recipientId);

        if (! $recipient) {
            return;
        }

        $isFirstOpen = $recipient->open_count === 0;

        $recipient->increment('open_count');
        $recipient->increment('engagement_score', 5);

        if ($isFirstOpen) {
            $recipient->update(['opened_at' => now()]);
            $recipient->emailProgram->increment('total_opened');
            $recipient->emailProgramStep?->increment('opened_count');
        }
    }

    /**
     * Track email click
     */
    public function trackClick(int $recipientId): void
    {
        $recipient = EmailProgramRecipient::find($recipientId);

        if (! $recipient) {
            return;
        }

        $isFirstClick = $recipient->click_count === 0;

        $recipient->increment('click_count');
        $recipient->increment('engagement_score', 10);

        if ($isFirstClick) {
            $recipient->update(['clicked_at' => now()]);
            $recipient->emailProgram->increment('total_clicked');
            $recipient->emailProgramStep?->increment('clicked_count');
        }
    }

    /**
     * Select A/B test winner based on configured metric
     */
    public function selectAbTestWinner(EmailProgram $program): ?string
    {
        if (! $program->is_ab_test) {
            return null;
        }

        $steps = $program->steps()
            ->whereNotNull('variant_name')
            ->get();

        if ($steps->isEmpty()) {
            return null;
        }

        $winner = match ($program->ab_test_winner_metric) {
            'open_rate' => $steps->sortByDesc(fn ($step): int|float => $step->sent_count > 0 ? ($step->opened_count / $step->sent_count) : 0)->first(),
            'click_rate' => $steps->sortByDesc(fn ($step): int|float => $step->sent_count > 0 ? ($step->clicked_count / $step->sent_count) : 0)->first(),
            'delivery_rate' => $steps->sortByDesc(fn ($step): int|float => $step->sent_count > 0 ? ($step->delivered_count / $step->sent_count) : 0)->first(),
            default => null,
        };

        if ($winner) {
            $program->update([
                'ab_test_winner_variant' => $winner->variant_name,
                'ab_test_winner_selected_at' => now(),
            ]);

            return $winner->variant_name;
        }

        return null;
    }

    /**
     * Calculate daily analytics for a program
     */
    public function calculateDailyAnalytics(EmailProgram $program, Carbon $date): void
    {
        $steps = $program->steps;

        foreach ($steps as $step) {
            $metrics = $this->calculateStepMetrics($step, $date);

            $program->analytics()->updateOrCreate(
                [
                    'email_program_id' => $program->id,
                    'email_program_step_id' => $step->id,
                    'date' => $date,
                ],
                $metrics
            );
        }

        // Calculate program-level analytics
        $programMetrics = $this->calculateProgramMetrics($program, $date);

        $program->analytics()->updateOrCreate(
            [
                'email_program_id' => $program->id,
                'email_program_step_id' => null,
                'date' => $date,
            ],
            $programMetrics
        );
    }

    /**
     * Apply personalization to email content
     */
    public function personalizeContent(string $content, EmailProgramRecipient $recipient): string
    {
        $replacements = [
            '{{first_name}}' => $recipient->first_name ?? '',
            '{{last_name}}' => $recipient->last_name ?? '',
            '{{email}}' => $recipient->email,
        ];

        if ($recipient->custom_fields) {
            foreach ($recipient->custom_fields as $key => $value) {
                $replacements["{{{$key}}}"] = $value;
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Check if email is unsubscribed
     */
    private function isUnsubscribed(int $teamId, string $email): bool
    {
        return EmailProgramUnsubscribe::query()
            ->where('team_id', $teamId)
            ->where('email', $email)
            ->exists();
    }

    /**
     * Check if email has hard bounce
     */
    private function hasHardBounce(string $email): bool
    {
        return EmailProgramBounce::query()
            ->where('email', $email)
            ->where('bounce_type', BounceType::HARD)
            ->exists();
    }

    /**
     * Build audience query based on filters
     */
    private function buildAudienceQuery(EmailProgram $program): Builder
    {
        // This is a simplified implementation
        // In production, you'd parse the audience_filters JSON and build a dynamic query
        return DB::table('people')
            ->where('team_id', $program->team_id)
            ->whereNotNull('primary_email')
            ->select('id', 'name', 'primary_email as email');
    }

    /**
     * Create recipient record
     */
    private function createRecipient(EmailProgram $program, object $recipient): void
    {
        $firstStep = $program->steps()->orderBy('step_order')->first();

        if (! $firstStep) {
            return;
        }

        $scheduledSendAt = $this->calculateScheduledSendTime($program, $firstStep);

        EmailProgramRecipient::create([
            'email_program_id' => $program->id,
            'email_program_step_id' => $firstStep->id,
            'email' => $recipient->email,
            'first_name' => $this->extractFirstName($recipient->name ?? ''),
            'last_name' => $this->extractLastName($recipient->name ?? ''),
            'recipient_type' => \App\Models\People::class,
            'recipient_id' => $recipient->id,
            'status' => EmailSendStatus::PENDING,
            'scheduled_send_at' => $scheduledSendAt,
        ]);
    }

    /**
     * Calculate scheduled send time based on program settings
     */
    private function calculateScheduledSendTime(EmailProgram $program, EmailProgramStep $step): Carbon
    {
        $baseTime = $program->scheduled_start_at ?? now();

        if ($step->step_order > 0) {
            return match ($step->delay_unit) {
                'minutes' => $baseTime->copy()->addMinutes($step->delay_value),
                'hours' => $baseTime->copy()->addHours($step->delay_value),
                'days' => $baseTime->copy()->addDays($step->delay_value),
                'weeks' => $baseTime->copy()->addWeeks($step->delay_value),
                default => $baseTime,
            };
        }

        return $baseTime;
    }

    /**
     * Check if sending should be throttled
     */
    private function shouldThrottle(EmailProgram $program, Carbon $now): bool
    {
        if (! $program->throttle_rate_per_hour) {
            return false;
        }

        $sentInLastHour = EmailProgramRecipient::query()
            ->where('email_program_id', $program->id)
            ->where('sent_at', '>=', $now->copy()->subHour())
            ->count();

        return $sentInLastHour >= $program->throttle_rate_per_hour;
    }

    /**
     * Check if current time is in quiet hours
     */
    private function isInQuietHours(EmailProgram $program, Carbon $now): bool
    {
        if (! $program->respect_quiet_hours || ! $program->quiet_hours_start || ! $program->quiet_hours_end) {
            return false;
        }

        $currentTime = $now->format('H:i:s');

        return $currentTime >= $program->quiet_hours_start && $currentTime <= $program->quiet_hours_end;
    }

    /**
     * Reschedule send after quiet hours
     */
    private function rescheduleAfterQuietHours(EmailProgramRecipient $recipient, Carbon $now): void
    {
        $program = $recipient->emailProgram;
        $quietHoursEnd = Carbon::parse($program->quiet_hours_end);

        $newScheduledTime = $now->copy()->setTimeFromTimeString($quietHoursEnd->format('H:i:s'));

        if ($newScheduledTime->isPast()) {
            $newScheduledTime->addDay();
        }

        $recipient->update(['scheduled_send_at' => $newScheduledTime]);
    }

    /**
     * Evaluate conditional send rules
     */
    private function evaluateConditionalSendRules(EmailProgramRecipient $recipient): bool
    {
        $step = $recipient->emailProgramStep;

        if (! $step || ! $step->conditional_send_rules) {
            return true;
        }

        // Simplified implementation - in production, you'd evaluate complex rules
        $rules = $step->conditional_send_rules;

        return ! (isset($rules['min_engagement_score']) && $recipient->engagement_score < $rules['min_engagement_score']);
    }

    /**
     * Send email to recipient
     */
    private function sendEmail(EmailProgramRecipient $recipient): void
    {
        $step = $recipient->emailProgramStep;

        if (! $step) {
            return;
        }

        // Personalize content
        $this->personalizeContent($step->html_content ?? '', $recipient);
        $this->personalizeContent($step->subject_line, $recipient);

        // In production, integrate with actual email service (Mailcoach, etc.)
        // For now, just update status

        $recipient->update([
            'status' => EmailSendStatus::SENT,
            'sent_at' => now(),
        ]);

        $recipient->emailProgram->increment('total_sent');
        $step->increment('sent_count');
    }

    /**
     * Calculate step metrics for a date
     */
    private function calculateStepMetrics(EmailProgramStep $step, Carbon $date): array
    {
        $recipients = $step->recipients()
            ->whereDate('sent_at', $date)
            ->get();

        $sentCount = $recipients->count();
        $deliveredCount = $recipients->where('status', EmailSendStatus::DELIVERED)->count();
        $openedCount = $recipients->where('open_count', '>', 0)->count();
        $clickedCount = $recipients->where('click_count', '>', 0)->count();
        $bouncedCount = $recipients->where('status', EmailSendStatus::BOUNCED)->count();
        $unsubscribedCount = $recipients->where('status', EmailSendStatus::UNSUBSCRIBED)->count();

        return [
            'sent_count' => $sentCount,
            'delivered_count' => $deliveredCount,
            'opened_count' => $recipients->sum('open_count'),
            'unique_opens' => $openedCount,
            'clicked_count' => $recipients->sum('click_count'),
            'unique_clicks' => $clickedCount,
            'bounced_count' => $bouncedCount,
            'unsubscribed_count' => $unsubscribedCount,
            'complained_count' => 0,
            'delivery_rate' => $sentCount > 0 ? round(($deliveredCount / $sentCount) * 100, 2) : 0,
            'open_rate' => $deliveredCount > 0 ? round(($openedCount / $deliveredCount) * 100, 2) : 0,
            'click_rate' => $deliveredCount > 0 ? round(($clickedCount / $deliveredCount) * 100, 2) : 0,
            'bounce_rate' => $sentCount > 0 ? round(($bouncedCount / $sentCount) * 100, 2) : 0,
            'unsubscribe_rate' => $deliveredCount > 0 ? round(($unsubscribedCount / $deliveredCount) * 100, 2) : 0,
        ];
    }

    /**
     * Calculate program-level metrics for a date
     */
    private function calculateProgramMetrics(EmailProgram $program, Carbon $date): array
    {
        $recipients = $program->recipients()
            ->whereDate('sent_at', $date)
            ->get();

        $sentCount = $recipients->count();
        $deliveredCount = $recipients->where('status', EmailSendStatus::DELIVERED)->count();
        $openedCount = $recipients->where('open_count', '>', 0)->count();
        $clickedCount = $recipients->where('click_count', '>', 0)->count();
        $bouncedCount = $recipients->where('status', EmailSendStatus::BOUNCED)->count();
        $unsubscribedCount = $recipients->where('status', EmailSendStatus::UNSUBSCRIBED)->count();

        return [
            'sent_count' => $sentCount,
            'delivered_count' => $deliveredCount,
            'opened_count' => $recipients->sum('open_count'),
            'unique_opens' => $openedCount,
            'clicked_count' => $recipients->sum('click_count'),
            'unique_clicks' => $clickedCount,
            'bounced_count' => $bouncedCount,
            'unsubscribed_count' => $unsubscribedCount,
            'complained_count' => 0,
            'delivery_rate' => $sentCount > 0 ? round(($deliveredCount / $sentCount) * 100, 2) : 0,
            'open_rate' => $deliveredCount > 0 ? round(($openedCount / $deliveredCount) * 100, 2) : 0,
            'click_rate' => $deliveredCount > 0 ? round(($clickedCount / $deliveredCount) * 100, 2) : 0,
            'bounce_rate' => $sentCount > 0 ? round(($bouncedCount / $sentCount) * 100, 2) : 0,
            'unsubscribe_rate' => $deliveredCount > 0 ? round(($unsubscribedCount / $deliveredCount) * 100, 2) : 0,
        ];
    }

    /**
     * Extract first name from full name
     */
    private function extractFirstName(string $name): string
    {
        $parts = explode(' ', trim($name));

        return $parts[0] ?? '';
    }

    /**
     * Extract last name from full name
     */
    private function extractLastName(string $name): string
    {
        $parts = explode(' ', trim($name));

        if (count($parts) > 1) {
            array_shift($parts);

            return implode(' ', $parts);
        }

        return '';
    }
}
