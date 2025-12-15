<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polymorphic email address placeholder.
 */
final class Email extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'emailable_id',
        'emailable_type',
        'label',
        'email',
        'is_primary',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function emailable(): MorphTo
    {
        return $this->morphTo();
    }
}
