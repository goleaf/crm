<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ImportJob extends Model
{
    use HasFactory;
    use HasTeam;

    protected $fillable = [
        'team_id',
        'user_id',
        'name',
        'type',
        'model_type',
        'file_path',
        'original_filename',
        'file_size',
        'status',
        'mapping',
        'duplicate_rules',
        'validation_rules',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'duplicate_rows',
        'errors',
        'preview_data',
        'statistics',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'mapping' => 'array',
        'duplicate_rules' => 'array',
        'validation_rules' => 'array',
        'errors' => 'array',
        'preview_data' => 'array',
        'statistics' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getSuccessRate(): float
    {
        if ($this->total_rows === 0) {
            return 0.0;
        }

        return ($this->successful_rows / $this->total_rows) * 100;
    }

    public function getErrorRate(): float
    {
        if ($this->total_rows === 0) {
            return 0.0;
        }

        return ($this->failed_rows / $this->total_rows) * 100;
    }

    public function getDuplicateRate(): float
    {
        if ($this->total_rows === 0) {
            return 0.0;
        }

        return ($this->duplicate_rows / $this->total_rows) * 100;
    }
}
