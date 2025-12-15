<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EmailProgramUnsubscribe extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'email',
        'email_program_id',
        'reason',
        'feedback',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\EmailProgram, $this>
     */
    public function emailProgram(): BelongsTo
    {
        return $this->belongsTo(EmailProgram::class);
    }
}
