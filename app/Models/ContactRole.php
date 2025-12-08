<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class ContactRole extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'description',
    ];

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsToMany<People, $this>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(People::class, 'contact_role_people');
    }
}
