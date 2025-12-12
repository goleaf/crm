<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Email message model for communication tracking.
 */
final class EmailMessage extends Model
{
    use HasCreator;
    use HasFactory;
    use HasTeam;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'creator_id',
        'subject',
        'body_html',
        'body_text',
        'from_email',
        'from_name',
        'to_emails',
        'cc_emails',
        'bcc_emails',
        'thread_id',
        'folder',
        'status',
        'scheduled_at',
        'sent_at',
        'read_receipt_requested',
        'importance',
        'attachments',
        'related_id',
        'related_type',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'to_emails' => 'array',
            'cc_emails' => 'array',
            'bcc_emails' => 'array',
            'attachments' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'read_receipt_requested' => 'boolean',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
