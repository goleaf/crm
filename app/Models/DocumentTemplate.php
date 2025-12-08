<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use Database\Factories\DocumentTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DocumentTemplate extends Model
{
    use HasCreator;

    /** @use HasFactory<DocumentTemplateFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'creator_id',
        'name',
        'description',
        'body',
        'is_default',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'template_id');
    }
}
