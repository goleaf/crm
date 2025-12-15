<?php

declare(strict_types=1);

namespace Tests\Support\Generators;

use App\Models\EmailMessage;
use App\Models\Team;
use App\Models\User;

/**
 * Generator for creating random EmailMessage instances for property-based testing.
 */
final class EmailGenerator
{
    /**
     * Generate a random email with all fields populated.
     *
     * @param array<string, mixed> $overrides
     */
    public static function generate(Team $team, ?User $creator = null, array $overrides = []): EmailMessage
    {
        $creator ??= User::factory()->create();

        $data = array_merge([
            'team_id' => $team->id,
            'creator_id' => $creator->id,
            'subject' => fake()->sentence(6),
            'body_html' => fake()->randomHtml(),
            'body_text' => fake()->paragraph(5),
            'from_email' => fake()->safeEmail(),
            'from_name' => fake()->name(),
            'to_emails' => self::generateEmailList(1, 3),
            'cc_emails' => fake()->optional(0.3)->passthrough(self::generateEmailList(1, 2)),
            'bcc_emails' => fake()->optional(0.2)->passthrough(self::generateEmailList(1, 2)),
            'thread_id' => fake()->optional(0.6)->uuid(),
            'folder' => fake()->optional(0.8)->randomElement(['inbox', 'sent', 'drafts', 'archive']),
            'status' => fake()->randomElement(['draft', 'sent', 'delivered', 'failed', 'scheduled']),
            'scheduled_at' => fake()->optional(0.2)->dateTimeBetween('now', '+1 week'),
            'sent_at' => fake()->optional(0.7)->dateTimeBetween('-1 month', 'now'),
            'read_receipt_requested' => fake()->boolean(10),
            'importance' => fake()->randomElement(['low', 'normal', 'high']),
            'attachments' => fake()->optional(0.4)->passthrough(self::generateAttachments()),
        ], $overrides);

        return EmailMessage::factory()->create($data);
    }

    /**
     * Generate a scheduled email.
     *
     * @param array<string, mixed> $overrides
     */
    public static function generateScheduled(Team $team, ?User $creator = null, array $overrides = []): EmailMessage
    {
        return self::generate($team, $creator, array_merge([
            'status' => 'scheduled',
            'scheduled_at' => fake()->dateTimeBetween('+1 hour', '+1 week'),
            'sent_at' => null,
        ], $overrides));
    }

    /**
     * Generate an email with thread.
     *
     * @param array<string, mixed> $overrides
     */
    public static function generateWithThread(Team $team, ?string $threadId = null, ?User $creator = null, array $overrides = []): EmailMessage
    {
        $threadId ??= fake()->uuid();

        return self::generate($team, $creator, array_merge([
            'thread_id' => $threadId,
            'subject' => 'Re: ' . fake()->sentence(4),
        ], $overrides));
    }

    /**
     * Generate an email linked to a record.
     *
     * @param array<string, mixed> $overrides
     */
    public static function generateWithRelated(Team $team, object $relatedRecord, ?User $creator = null, array $overrides = []): EmailMessage
    {
        return self::generate($team, $creator, array_merge([
            'related_id' => $relatedRecord->id,
            'related_type' => $relatedRecord::class,
        ], $overrides));
    }

    /**
     * Generate a list of email addresses.
     *
     * @return array<int, array{email: string, name?: string}>
     */
    private static function generateEmailList(int $min = 1, int $max = 3): array
    {
        $count = fake()->numberBetween($min, $max);
        $emails = [];

        for ($i = 0; $i < $count; $i++) {
            $emails[] = [
                'email' => fake()->safeEmail(),
                'name' => fake()->optional(0.7)->name(),
            ];
        }

        return $emails;
    }

    /**
     * Generate attachments array.
     *
     * @return array<int, array{name: string, size: int, type: string}>
     */
    private static function generateAttachments(): array
    {
        $count = fake()->numberBetween(1, 4);
        $attachments = [];

        for ($i = 0; $i < $count; $i++) {
            $attachments[] = [
                'name' => fake()->word() . '.' . fake()->fileExtension(),
                'size' => fake()->numberBetween(1024, 5242880), // 1KB to 5MB
                'type' => fake()->mimeType(),
            ];
        }

        return $attachments;
    }

    /**
     * Generate random email data without creating a model.
     *
     * @return array<string, mixed>
     */
    public static function generateData(Team $team, ?User $creator = null): array
    {
        $creator ??= User::factory()->create();

        return [
            'team_id' => $team->id,
            'creator_id' => $creator->id,
            'subject' => fake()->sentence(6),
            'body_html' => fake()->randomHtml(),
            'body_text' => fake()->paragraph(5),
            'from_email' => fake()->safeEmail(),
            'from_name' => fake()->name(),
            'to_emails' => self::generateEmailList(1, 3),
            'status' => fake()->randomElement(['draft', 'sent', 'delivered', 'failed']),
        ];
    }
}
