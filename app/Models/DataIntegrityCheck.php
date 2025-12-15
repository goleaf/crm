<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DataIntegrityCheckStatus;
use App\Enums\DataIntegrityCheckType;
use Database\Factories\DataIntegrityCheckFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                             $id
 * @property int                             $team_id
 * @property DataIntegrityCheckType          $type
 * @property DataIntegrityCheckStatus        $status
 * @property string|null                     $target_model
 * @property array|null                      $check_parameters
 * @property array|null                      $results
 * @property int                             $issues_found
 * @property int                             $issues_fixed
 * @property string|null                     $error_message
 * @property int|null                        $created_by
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 */
final class DataIntegrityCheck extends Model
{
    /** @use HasFactory<DataIntegrityCheckFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'type',
        'status',
        'target_model',
        'check_parameters',
        'results',
        'issues_found',
        'issues_fixed',
        'error_message',
        'created_by',
        'started_at',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DataIntegrityCheckType::class,
            'status' => DataIntegrityCheckStatus::class,
            'check_parameters' => 'array',
            'results' => 'array',
            'issues_found' => 'integer',
            'issues_fixed' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * The team that owns this integrity check.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * The user who created this integrity check.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the integrity check is pending.
     */
    public function isPending(): bool
    {
        return $this->status === DataIntegrityCheckStatus::PENDING;
    }

    /**
     * Check if the integrity check is running.
     */
    public function isRunning(): bool
    {
        return $this->status === DataIntegrityCheckStatus::RUNNING;
    }

    /**
     * Check if the integrity check is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === DataIntegrityCheckStatus::COMPLETED;
    }

    /**
     * Check if the integrity check has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === DataIntegrityCheckStatus::FAILED;
    }

    /**
     * Get the duration of the check in seconds.
     */
    public function getDurationInSeconds(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }
}
