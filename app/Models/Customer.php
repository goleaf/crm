<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-only projection combining companies and people.
 */
final class Customer extends Model
{
    protected $table = 'customers_view';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uid',
        'entity_id',
        'team_id',
        'type',
        'name',
        'email',
        'phone',
        'created_at',
    ];

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
