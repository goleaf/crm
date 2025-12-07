<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polymorphic address placeholder.
 */
final class Address extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'addressable_id',
        'addressable_type',
        'label',
        'line1',
        'line2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_primary',
        'type',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}
