<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use Database\Factories\PdfTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $creator_id
 * @property string $name
 * @property string $key
 * @property string|null $entity_type
 * @property string|null $description
 * @property string $layout
 * @property array|null $merge_fields
 * @property array|null $styling
 * @property array|null $watermark
 * @property array|null $permissions
 * @property bool $encryption_enabled
 * @property string|null $encryption_password
 * @property int $version
 * @property int|null $parent_template_id
 * @property bool $is_active
 * @property bool $is_archived
 * @property Carbon|null $archived_at
 * @property array|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
final class PdfTemplate extends Model
{
    use HasCreator;

    /** @use HasFactory<PdfTemplateFactory> */
    use HasFactory;

    use HasTeam;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'creator_id',
        'name',
        'key',
        'entity_type',
        'description',
        'layout',
        'merge_fields',
        'styling',
        'watermark',
        'permissions',
        'encryption_enabled',
        'encryption_password',
        'version',
        'parent_template_id',
        'is_active',
        'is_archived',
        'archived_at',
        'metadata',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'version' => 1,
        'is_active' => true,
        'is_archived' => false,
        'encryption_enabled' => false,
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'merge_fields' => 'array',
            'styling' => 'array',
            'watermark' => 'array',
            'permissions' => 'array',
            'metadata' => 'array',
            'encryption_enabled' => 'boolean',
            'is_active' => 'boolean',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PdfTemplate, $this>
     */
    public function parentTemplate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_template_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\PdfTemplate, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_template_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\PdfGeneration, $this>
     */
    public function generations(): HasMany
    {
        return $this->hasMany(PdfGeneration::class);
    }

    public function createNewVersion(): self
    {
        $newVersion = $this->replicate(['version', 'is_active']);
        $newVersion->version = $this->version + 1;
        $newVersion->parent_template_id = $this->parent_template_id ?? $this->id;
        $newVersion->is_active = false;
        $newVersion->save();

        return $newVersion;
    }

    public function archive(): void
    {
        $this->update([
            'is_archived' => true,
            'is_active' => false,
            'archived_at' => now(),
        ]);
    }

    public function activate(): void
    {
        if ($this->parent_template_id !== null) {
            self::where('parent_template_id', $this->parent_template_id)
                ->where('id', '!=', $this->id)
                ->update(['is_active' => false]);
        }

        $this->update([
            'is_active' => true,
            'is_archived' => false,
        ]);
    }

    protected static function booted(): void
    {
        self::creating(function (self $template): void {
            if ($template->team_id === null && auth('web')->check()) {
                $template->team_id = auth('web')->user()?->currentTeam?->getKey();
            }

            if ($template->creator_id === null && auth('web')->check()) {
                $template->creator_id = auth('web')->id();
            }
        });
    }
}
