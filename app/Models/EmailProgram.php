<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmailProgramStatus;
use App\Enums\EmailProgramType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EmailProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'name',
        'description',
        'type',
        'status',
        'audience_filters',
        'estimated_audience_size',
        'scheduled_start_at',
        'scheduled_end_at',
        'started_at',
        'completed_at',
        'is_ab_test',
        'ab_test_sample_size_percent',
        'ab_test_winner_metric',
        'ab_test_winner_selected_at',
        'ab_test_winner_variant',
        'personalization_rules',
        'dynamic_content_blocks',
        'scoring_rules',
        'min_engagement_score',
        'throttle_rate_per_hour',
        'send_time_optimization',
        'respect_quiet_hours',
        'quiet_hours_start',
        'quiet_hours_end',
        'total_recipients',
        'total_sent',
        'total_delivered',
        'total_opened',
        'total_clicked',
        'total_bounced',
        'total_unsubscribed',
        'total_complained',
    ];

    protected $casts = [
        'type' => EmailProgramType::class,
        'status' => EmailProgramStatus::class,
        'audience_filters' => 'array',
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_ab_test' => 'boolean',
        'ab_test_winner_selected_at' => 'datetime',
        'personalization_rules' => 'array',
        'dynamic_content_blocks' => 'array',
        'scoring_rules' => 'array',
        'send_time_optimization' => 'array',
        'respect_quiet_hours' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(EmailProgramStep::class)->orderBy('step_order');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailProgramRecipient::class);
    }

    public function unsubscribes(): HasMany
    {
        return $this->hasMany(EmailProgramUnsubscribe::class);
    }

    public function bounces(): HasMany
    {
        return $this->hasMany(EmailProgramBounce::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(EmailProgramAnalytic::class);
    }
}
