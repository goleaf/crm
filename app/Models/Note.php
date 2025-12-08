<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\NoteCategory;
use App\Enums\NoteVisibility;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Note extends Model
{
    /** @use HasFactory<\Database\Factories\NoteFactory> */
    use HasFactory;

    use HasTeam;
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'creator_id',
        'title',
        'category',
        'visibility',
        'is_template',
        'creation_source',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB->value,
    ];

    protected $casts = [
        'is_template' => 'boolean',
        'category' => NoteCategory::class,
        'visibility' => NoteVisibility::class,
        'creation_source' => CreationSource::class,
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
