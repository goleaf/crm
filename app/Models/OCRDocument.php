<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class OCRDocument extends Model
{
    use BelongsToTeam;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'template_id',
        'user_id',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'status',
        'extracted_data',
        'raw_text',
        'confidence_score',
        'processing_time',
        'validation_errors',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'validation_errors' => 'array',
        'confidence_score' => 'decimal:4',
        'processing_time' => 'decimal:3',
        'file_size' => 'integer',
        'processed_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\OCRTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(OCRTemplate::class, 'template_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function pending($query)
    {
        return $query->where('status', 'pending');
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function processing($query)
    {
        return $query->where('status', 'processing');
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function completed($query)
    {
        return $query->where('status', 'completed');
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function failed($query)
    {
        return $query->where('status', 'failed');
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted(array $data, float $confidence, float $processingTime): void
    {
        $this->update([
            'status' => 'completed',
            'extracted_data' => $data,
            'confidence_score' => $confidence,
            'processing_time' => $processingTime,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'processed_at' => now(),
        ]);
    }

    protected function getConfidencePercentageAttribute(): ?float
    {
        return $this->confidence_score ? $this->confidence_score * 100 : null;
    }

    protected function getConfidenceColorAttribute(): string
    {
        if ($this->confidence_score === null) {
            return 'gray';
        }

        return match (true) {
            $this->confidence_score >= 0.9 => 'success',
            $this->confidence_score >= 0.7 => 'warning',
            default => 'danger',
        };
    }
}
