<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Call record model for communication tracking.
 */
final class Call extends Model
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
        'direction',
        'phone_number',
        'contact_name',
        'purpose',
        'outcome',
        'duration_minutes',
        'scheduled_at',
        'started_at',
        'ended_at',
        'status',
        'participants',
        'notes',
        'follow_up_required',
        'voip_call_id',
        'recording_url',
        'related_id',
        'related_type',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'participants' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'follow_up_required' => 'boolean',
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
