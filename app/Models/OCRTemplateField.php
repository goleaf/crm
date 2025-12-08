<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OCRTemplateField extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'field_name',
        'field_type',
        'extraction_pattern',
        'required',
        'validation_rules',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'required' => 'boolean',
        'validation_rules' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\OCRTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(OCRTemplate::class, 'template_id');
    }
}
