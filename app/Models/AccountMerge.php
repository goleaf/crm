<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AccountMergeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $primary_company_id
 * @property int $duplicate_company_id
 * @property int|null $merged_by_user_id
 * @property array|null $field_selections
 * @property array|null $transferred_relationships
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class AccountMerge extends Model
{
    /** @use HasFactory<AccountMergeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'primary_company_id',
        'duplicate_company_id',
        'merged_by_user_id',
        'field_selections',
        'transferred_relationships',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'field_selections' => 'array',
            'transferred_relationships' => 'array',
        ];
    }

    /**
     * The primary company that was kept after the merge.
     *
     * @return BelongsTo<Company, $this>
     */
    public function primaryCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'primary_company_id');
    }

    /**
     * The duplicate company that was merged into the primary.
     *
     * @return BelongsTo<Company, $this>
     */
    public function duplicateCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'duplicate_company_id');
    }

    /**
     * The user who performed the merge operation.
     *
     * @return BelongsTo<User, $this>
     */
    public function mergedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by_user_id');
    }
}
