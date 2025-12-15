<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use MohamedSaid\Notable\Notable;

/**
 * @property-read \Illuminate\Database\Eloquent\Model|null $notable
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 */
final class NotableEntry extends Notable
{
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'note',
        'notable_type',
        'notable_id',
        'creator_type',
        'creator_id',
        'team_id',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function notable(): MorphTo
    {
        return parent::notable();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function creator(): MorphTo
    {
        return parent::creator();
    }
}
