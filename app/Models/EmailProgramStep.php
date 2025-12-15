<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class EmailProgramStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_program_id',
        'step_order',
        'name',
        'description',
        'subject_line',
        'preview_text',
        'html_content',
        'plain_text_content',
        'from_name',
        'from_email',
        'reply_to_email',
        'variant_name',
        'is_control',
        'delay_value',
        'delay_unit',
        'conditional_send_rules',
        'recipients_count',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'bounced_count',
        'unsubscribed_count',
    ];

    protected $casts = [
        'is_control' => 'boolean',
        'conditional_send_rules' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\EmailProgram, $this>
     */
    public function emailProgram(): BelongsTo
    {
        return $this->belongsTo(EmailProgram::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\EmailProgramRecipient, $this>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(EmailProgramRecipient::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\EmailProgramAnalytic, $this>
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(EmailProgramAnalytic::class);
    }
}
