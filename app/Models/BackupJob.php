<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BackupJobStatus;
use App\Enums\BackupJobType;
use Database\Factories\BackupJobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                             $id
 * @property int                             $team_id
 * @property BackupJobType                   $type
 * @property BackupJobStatus                 $status
 * @property string                          $name
 * @property string|null                     $description
 * @property array|null                      $backup_config
 * @property string|null                     $backup_path
 * @property int|null                        $file_size
 * @property string|null                     $checksum
 * @property array|null                      $verification_results
 * @property string|null                     $error_message
 * @property int|null                        $created_by
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 */
final class BackupJob extends Model
{
    /** @use HasFactory<BackupJobFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'type',
        'status',
        'name',
        'description',
        'backup_config',
        'backup_path',
        'file_size',
        'checksum',
        'verification_results',
        'error_message',
        'created_by',
        'started_at',
        'completed_at',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => BackupJobType::class,
            'status' => BackupJobStatus::class,
            'backup_config' => 'array',
            'file_size' => 'integer',
            'verification_results' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * The team that owns this backup job.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * The user who created this backup job.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the backup job is pending.
     */
    public function isPending(): bool
    {
        return $this->status === BackupJobStatus::PENDING;
    }

    /**
     * Check if the backup job is running.
     */
    public function isRunning(): bool
    {
        return $this->status === BackupJobStatus::RUNNING;
    }

    /**
     * Check if the backup job is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === BackupJobStatus::COMPLETED;
    }

    /**
     * Check if the backup job has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === BackupJobStatus::FAILED;
    }

    /**
     * Check if the backup is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get the duration of the backup in seconds.
     */
    public function getDurationInSeconds(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * Get the formatted file size.
     */
    public function getFormattedFileSize(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
