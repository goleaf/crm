<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BounceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EmailProgramBounce extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_program_id',
        'email_program_recipient_id',
        'email',
        'bounce_type',
        'bounce_reason',
        'diagnostic_code',
        'raw_message',
    ];

    protected $casts = [
        'bounce_type' => BounceType::class,
        'raw_message' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\EmailProgram, $this>
     */
    public function emailProgram(): BelongsTo
    {
        return $this->belongsTo(EmailProgram::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\EmailProgramRecipient, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(EmailProgramRecipient::class, 'email_program_recipient_id');
    }
}
