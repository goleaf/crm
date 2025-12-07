<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function emailProgram(): BelongsTo
    {
        return $this->belongsTo(EmailProgram::class);
    }
}
