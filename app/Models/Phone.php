<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polymorphic phone number placeholder.
 */
final class Phone extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'phoneable_id',
        'phoneable_type',
        'label',
        'number',
        'type',
        'is_primary',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function phoneable(): MorphTo
    {
        return $this->morphTo();
    }
}
