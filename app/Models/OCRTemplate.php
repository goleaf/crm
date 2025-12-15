<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class OCRTemplate extends Model
{
    use BelongsToTeam;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'name',
        'description',
        'document_type',
        'field_definitions',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'field_definitions' => 'array',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\OCRTemplateField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(OCRTemplateField::class, 'template_id')->orderBy('sort_order');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\OCRDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(OCRDocument::class, 'template_id');
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function active($query)
    {
        return $query->where('is_active', true);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function byDocumentType($query, string $type)
    {
        return $query->where('document_type', $type);
    }
}
