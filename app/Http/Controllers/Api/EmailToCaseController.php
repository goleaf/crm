<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\CaseChannel;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Enums\CreationSource;
use App\Http\Controllers\Controller;
use App\Models\People;
use App\Models\SupportCase;
use App\Services\CaseQueueRoutingService;
use App\Services\CaseSlaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EmailToCaseController extends Controller
{
    public function inbound(Request $request): JsonResponse
    {
        $user = $request->user();
        $team = $user?->currentTeam;

        if ($team === null) {
            abort(403, 'No active team context.');
        }

        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'from' => ['required', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'to' => ['nullable', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'html_body' => ['nullable', 'string'],
            'message_id' => ['required', 'string', 'max:255'],
            'in_reply_to' => ['nullable', 'string', 'max:255'],
            'references' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.filename' => ['required_with:attachments', 'string', 'max:255'],
            'attachments.*.content_type' => ['required_with:attachments', 'string', 'max:255'],
            'attachments.*.size' => ['required_with:attachments', 'integer', 'min:0'],
            'attachments.*.content' => ['required_with:attachments', 'string'],
            'headers' => ['nullable', 'array'],
        ]);

        $headers = is_array($validated['headers'] ?? null) ? $validated['headers'] : [];
        $autoSubmitted = strtolower((string) ($headers['Auto-Submitted'] ?? ''));
        $autoReply = strtolower((string) ($headers['X-Autoreply'] ?? ''));

        if ($autoSubmitted === 'auto-reply' || $autoReply === 'yes') {
            return response()->json([
                'success' => true,
                'message' => 'Auto-reply ignored',
            ], 200);
        }

        $spamFlag = strtoupper((string) ($headers['X-Spam-Flag'] ?? ''));
        $spamScore = (float) ($headers['X-Spam-Score'] ?? 0);

        if ($spamFlag === 'YES' || $spamScore >= 8.0) {
            return response()->json([
                'success' => false,
                'message' => 'Spam detected',
            ], 422);
        }

        $messageId = (string) $validated['message_id'];
        $inReplyTo = $validated['in_reply_to'] ?? null;

        // Duplicate message protection
        $duplicateCase = SupportCase::query()
            ->where('team_id', $team->getKey())
            ->where('email_message_id', $messageId)
            ->first();

        if ($duplicateCase instanceof SupportCase) {
            return response()->json([
                'success' => true,
                'message' => 'Duplicate message ignored',
                'case_id' => $duplicateCase->getKey(),
            ], 200);
        }

        $result = DB::transaction(function () use ($validated, $headers, $team, $user, $messageId, $inReplyTo): array {
            $case = $this->findThreadCase($team->getKey(), $inReplyTo);
            $created = false;

            if (! $case instanceof SupportCase) {
                $created = true;

                $contact = $this->findOrCreateContact(
                    teamId: (int) $team->getKey(),
                    email: (string) $validated['from'],
                    name: $validated['from_name'] ?? null,
                );

                $priority = $this->determinePriority(
                    subject: (string) $validated['subject'],
                    headers: $headers,
                );

                $case = SupportCase::create([
                    'team_id' => $team->getKey(),
                    'creator_id' => $user?->getKey(),
                    'case_number' => 'CASE-' . Str::upper(Str::ulid()),
                    'subject' => $validated['subject'],
                    'description' => $validated['body'],
                    'status' => CaseStatus::NEW,
                    'priority' => $priority,
                    'type' => CaseType::QUESTION,
                    'channel' => CaseChannel::EMAIL,
                    'queue' => null,
                    'thread_reference' => $messageId,
                    'thread_id' => (string) Str::uuid(),
                    'email_message_id' => $messageId,
                    'contact_id' => $contact?->getKey(),
                    'company_id' => $contact?->company_id,
                    'assigned_team_id' => $team->getKey(),
                    'assigned_to_id' => $user?->getKey(),
                    'assigned_at' => $user !== null ? now() : null,
                    'creation_source' => CreationSource::EMAIL,
                ]);

                $slaDue = resolve(CaseSlaService::class)->calculateSlaDueDate($case);
                if ($slaDue !== null) {
                    $case->forceFill(['sla_due_at' => $slaDue])->save();
                }

                resolve(CaseQueueRoutingService::class)->assignQueue($case);
            }

            $case->emailMessages()->create([
                'team_id' => $team->getKey(),
                'creator_id' => $user?->getKey(),
                'subject' => $validated['subject'],
                'body_text' => $validated['body'],
                'body_html' => $validated['html_body'] ?? null,
                'from_email' => $validated['from'],
                'from_name' => $validated['from_name'] ?? null,
                'to_emails' => isset($validated['to']) ? [['email' => $validated['to'], 'name' => null]] : [],
                'message_id' => $messageId,
                'in_reply_to' => $validated['in_reply_to'] ?? null,
                'references' => $validated['references'] ?? null,
                'thread_id' => $case->thread_id,
                'status' => 'received',
                'importance' => 'normal',
                'read_receipt_requested' => false,
                'attachments' => $validated['attachments'] ?? [],
            ]);

            if ($created) {
                $case->activities()->create([
                    'team_id' => $team->getKey(),
                    'causer_id' => $user?->getKey(),
                    'event' => 'case_created_from_email',
                    'description' => 'Case created from email',
                    'changes' => [
                        'attributes' => [
                            'email_message_id' => $messageId,
                            'from' => $validated['from'],
                            'subject' => $validated['subject'],
                        ],
                    ],
                ]);

                if ($case->assigned_to_id !== null) {
                    $case->activities()->create([
                        'team_id' => $team->getKey(),
                        'causer_id' => $user?->getKey(),
                        'event' => 'case_assigned',
                        'description' => 'Case assigned',
                        'changes' => [
                            'attributes' => [
                                'assigned_to_id' => $case->assigned_to_id,
                            ],
                        ],
                    ]);
                }
            }

            return [
                'case' => $case,
                'created' => $created,
            ];
        });

        /** @var SupportCase $case */
        $case = $result['case'];
        $created = (bool) $result['created'];

        return response()->json([
            'success' => true,
            'message' => $created ? 'Case created' : 'Case updated',
            'case_id' => $case->getKey(),
        ], $created ? 201 : 200);
    }

    private function determinePriority(string $subject, array $headers): CasePriority
    {
        $priorityHeader = (string) ($headers['X-Priority'] ?? '');

        if ($priorityHeader === '1' || str_contains(strtolower($subject), 'urgent')) {
            return CasePriority::P1;
        }

        if ($priorityHeader === '2') {
            return CasePriority::P2;
        }

        if ($priorityHeader === '4' || $priorityHeader === '5') {
            return CasePriority::P4;
        }

        return CasePriority::P3;
    }

    private function findThreadCase(int $teamId, ?string $inReplyTo): ?SupportCase
    {
        if ($inReplyTo === null) {
            return null;
        }

        return SupportCase::query()
            ->where('team_id', $teamId)
            ->where(function (\Illuminate\Contracts\Database\Query\Builder $query) use ($inReplyTo): void {
                $query->where('email_message_id', $inReplyTo)
                    ->orWhere('thread_reference', $inReplyTo);
            })
            ->first();
    }

    private function findOrCreateContact(int $teamId, string $email, ?string $name): ?People
    {
        $contact = People::query()
            ->where('team_id', $teamId)
            ->where(function (\Illuminate\Contracts\Database\Query\Builder $query) use ($email): void {
                $query->where('primary_email', $email)
                    ->orWhere('alternate_email', $email);
            })
            ->first();

        if ($contact instanceof People) {
            return $contact;
        }

        return People::create([
            'team_id' => $teamId,
            'name' => $name ?: $email,
            'primary_email' => $email,
            'creation_source' => CreationSource::EMAIL,
        ]);
    }
}
