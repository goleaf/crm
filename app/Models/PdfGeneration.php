<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PdfGenerationStatus;
use App\Models\Concerns\HasTeam;
use Database\Factories\PdfGenerationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int                 $id
 * @property int                 $team_id
 * @property int                 $pdf_template_id
 * @property int|null            $user_id
 * @property string              $entity_type
 * @property int                 $entity_id
 * @property string              $file_path
 * @property string              $file_name
 * @property int|null            $file_size
 * @property int                 $page_count
 * @property array|null          $merge_data
 * @property array|null          $generation_options
 * @property bool                $has_watermark
 * @property bool                $is_encrypted
 * @property PdfGenerationStatus $status
 * @property string|null         $error_message
 * @property Carbon              $generated_at
 * @property Carbon|null         $created_at
 * @property Carbon|null         $updated_at
 */
final class PdfGeneration extends Model
{
    /** @use HasFactory<PdfGenerationFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'pdf_template_id',
        'user_id',
        'entity_type',
        'entity_id',
        'file_path',
        'file_name',
        'file_size',
        'page_count',
        'merge_data',
        'generation_options',
        'has_watermark',
        'is_encrypted',
        'status',
        'error_message',
        'generated_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'page_count' => 1,
        'has_watermark' => false,
        'is_encrypted' => false,
        'status' => PdfGenerationStatus::COMPLETED,
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'merge_data' => 'array',
            'generation_options' => 'array',
            'has_watermark' => 'boolean',
            'is_encrypted' => 'boolean',
            'status' => PdfGenerationStatus::class,
            'generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PdfTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(PdfTemplate::class, 'pdf_template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        self::creating(function (self $generation): void {
            if ($generation->team_id === null && auth('web')->check()) {
                $generation->team_id = auth('web')->user()?->currentTeam?->getKey();
            }

            if ($generation->user_id === null && auth('web')->check()) {
                $generation->user_id = auth('web')->id();
            }

            $generation->generated_at ??= now();
        });
    }
}
