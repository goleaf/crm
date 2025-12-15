<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EmailProgramAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_program_id',
        'email_program_step_id',
        'date',
        'sent_count',
        'delivered_count',
        'opened_count',
        'unique_opens',
        'clicked_count',
        'unique_clicks',
        'bounced_count',
        'unsubscribed_count',
        'complained_count',
        'delivery_rate',
        'open_rate',
        'click_rate',
        'bounce_rate',
        'unsubscribe_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'delivery_rate' => 'decimal:2',
        'open_rate' => 'decimal:2',
        'click_rate' => 'decimal:2',
        'bounce_rate' => 'decimal:2',
        'unsubscribe_rate' => 'decimal:2',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\EmailProgram, $this>
     */
    public function emailProgram(): BelongsTo
    {
        return $this->belongsTo(EmailProgram::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\EmailProgramStep, $this>
     */
    public function emailProgramStep(): BelongsTo
    {
        return $this->belongsTo(EmailProgramStep::class);
    }
}
