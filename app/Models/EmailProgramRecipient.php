<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmailSendStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class EmailProgramRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_program_id',
        'email_program_step_id',
        'email',
        'first_name',
        'last_name',
        'custom_fields',
        'recipient_type',
        'recipient_id',
        'status',
        'scheduled_send_at',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'unsubscribed_at',
        'open_count',
        'click_count',
        'engagement_score',
        'bounce_type',
        'bounce_reason',
        'error_message',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'status' => EmailSendStatus::class,
        'scheduled_send_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function emailProgram(): BelongsTo
    {
        return $this->belongsTo(EmailProgram::class);
    }

    public function emailProgramStep(): BelongsTo
    {
        return $this->belongsTo(EmailProgramStep::class);
    }

    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }
}
