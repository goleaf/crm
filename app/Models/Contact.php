<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Contact model - alias for People model for CRM terminology.
 * This provides a more intuitive name for the relationship context.
 */
final class Contact extends Model
{
    /** @use HasFactory<\Database\Factories\ContactFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'people';

    /**
     * Many-to-many relationship with accounts.
     *
     * @return BelongsToMany<Account, $this>
     */
    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_people', 'people_id', 'account_id')
            ->withPivot(['is_primary', 'role'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<PeopleEmail, $this>
     */
    public function emails(): HasMany
    {
        return $this->hasMany(PeopleEmail::class, 'people_id');
    }
}
