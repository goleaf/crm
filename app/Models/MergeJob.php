<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MergeJobStatus;
use App\Enums\MergeJobType;
use Database\Factories\MergeJobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int                             $id
 * @property int                             $team_id
 * @property MergeJobType                    $type
 * @property string                          $primary_model_type
 * @property int                             $primary_model_id
 * @property string                          $duplicate_model_type
 * @property int                             $duplicate_model_id
 * @property MergeJobStatus                  $status
 * @property array|null                      $merge_rules
 * @property array|null                      $field_selections
 * @property array|null                      $transferred_relationships
 * @property array|null                      $merge_preview
 * @property string|null                     $error_message
 * @property int|null                        $created_by
 * @property int|null                        $processed_by
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 */
final class MergeJob extends Model
{
    /** @use HasFactory<MergeJobFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'type',
        'primary_model_type',
        'primary_model_id',
        'duplicate_model_type',
        'duplicate_model_id',
        'status',
        'merge_rules',
        'field_selections',
        'transferred_relationships',
        'merge_preview',
        'error_message',
        'created_by',
        'processed_by',
        'processed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MergeJobType::class,
            'status' => MergeJobStatus::class,
            'merge_rules' => 'array',
            'field_selections' => 'array',
            'transferred_relationships' => 'array',
            'merge_preview' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * The team that owns this merge job.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * The user who created this merge job.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The user who processed this merge job.
     *
     * @return BelongsTo<User, $this>
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * The primary model being merged into.
     *
     * @return MorphTo<Model, $this>
     */
    public function primaryModel(): MorphTo
    {
        return $this->morphTo('primary_model', 'primary_model_type', 'primary_model_id');
    }

    /**
     * The duplicate model being merged.
     *
     * @return MorphTo<Model, $this>
     */
    public function duplicateModel(): MorphTo
    {
        return $this->morphTo('duplicate_model', 'duplicate_model_type', 'duplicate_model_id');
    }

    /**
     * Check if the merge job is pending.
     */
    public function isPending(): bool
    {
        return $this->status === MergeJobStatus::PENDING;
    }

    /**
     * Check if the merge job is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === MergeJobStatus::PROCESSING;
    }

    /**
     * Check if the merge job is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === MergeJobStatus::COMPLETED;
    }

    /**
     * Check if the merge job has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === MergeJobStatus::FAILED;
    }
}
