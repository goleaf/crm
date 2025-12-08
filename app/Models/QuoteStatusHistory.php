<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property QuoteStatus|null $from_status
 * @property QuoteStatus $to_status
 */
final class QuoteStatusHistory extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'quote_id',
        'team_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => QuoteStatus::class,
            'to_status' => QuoteStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
