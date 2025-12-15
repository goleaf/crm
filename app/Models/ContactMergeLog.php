<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ContactMergeLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'primary_contact_id',
        'duplicate_contact_id',
        'merged_by',
        'merge_data',
    ];

    /**
     * @return BelongsTo<People, $this>
     */
    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(People::class, 'primary_contact_id');
    }

    /**
     * @return BelongsTo<People, $this>
     */
    public function duplicateContact(): BelongsTo
    {
        return $this->belongsTo(People::class, 'duplicate_contact_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function mergedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'merge_data' => 'array',
        ];
    }
}
