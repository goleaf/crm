<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BulkOperationStatus;
use App\Enums\BulkOperationType;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BulkOperation extends Model
{
    use HasCreator, HasFactory;

    protected $fillable = [
        'type',
        'status',
        'model_type',
        'total_records',
        'processed_records',
        'failed_records',
        'batch_size',
        'operation_data',
        'errors',
        'started_at',
        'completed_at',
        'team_id',
    ];

    protected $casts = [
        'type' => BulkOperationType::class,
        'status' => BulkOperationStatus::class,
        'operation_data' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    protected function getProgressPercentageAttribute(): float
    {
        if ($this->total_records === 0) {
            return 0;
        }

        return round(($this->processed_records / $this->total_records) * 100, 2);
    }

    protected function getIsCompletedAttribute(): bool
    {
        return in_array($this->status, [
            BulkOperationStatus::COMPLETED,
            BulkOperationStatus::FAILED,
            BulkOperationStatus::CANCELLED,
        ]);
    }

    protected function getIsSuccessfulAttribute(): bool
    {
        return $this->status === BulkOperationStatus::COMPLETED && $this->failed_records === 0;
    }

    protected function getDurationAttribute(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();

        return $this->started_at->diffInSeconds($endTime);
    }
}
