<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $team_id
 * @property int         $user_id
 * @property string      $name
 * @property string      $model_type
 * @property string      $format
 * @property array|null  $template_config
 * @property array|null  $selected_fields
 * @property array|null  $filters
 * @property array|null  $options
 * @property string      $scope
 * @property array|null  $record_ids
 * @property string      $status
 * @property int         $total_records
 * @property int         $processed_records
 * @property int         $successful_records
 * @property int         $failed_records
 * @property string|null $file_path
 * @property string      $file_disk
 * @property int|null    $file_size
 * @property Carbon|null $expires_at
 * @property array|null  $errors
 * @property string|null $error_message
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 * @property Team        $team
 * @property User        $user
 */
final class ExportJob extends Model
{
    use HasCreator;
    use HasFactory;
    use HasTeam;

    protected $fillable = [
        'team_id',
        'user_id',
        'name',
        'model_type',
        'format',
        'template_config',
        'selected_fields',
        'filters',
        'options',
        'scope',
        'record_ids',
        'status',
        'total_records',
        'processed_records',
        'successful_records',
        'failed_records',
        'file_path',
        'file_disk',
        'file_size',
        'expires_at',
        'errors',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'template_config' => 'array',
        'selected_fields' => 'array',
        'filters' => 'array',
        'options' => 'array',
        'record_ids' => 'array',
        'errors' => 'array',
        'expires_at' => 'datetime',
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

    public function getProgressPercentage(): int
    {
        if ($this->total_records === 0) {
            return 0;
        }

        return (int) round(($this->processed_records / $this->total_records) * 100);
    }

    public function getSuccessRate(): float
    {
        if ($this->processed_records === 0) {
            return 0.0;
        }

        return round(($this->successful_records / $this->processed_records) * 100, 2);
    }

    public function hasErrors(): bool
    {
        return $this->failed_records > 0 || ! empty($this->errors) || ! empty($this->error_message);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getFileUrl(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return \Storage::disk($this->file_disk)->url($this->file_path);
    }

    public function getFileSizeFormatted(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        return \App\Support\Helpers\NumberHelper::fileSize($this->file_size);
    }
}
