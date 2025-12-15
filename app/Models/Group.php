<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Group extends Model
{
    use HasFactory;
    use HasTeam;

    protected $fillable = [
        'name',
        'description',
        'team_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\People, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(People::class)->withTimestamps();
    }
}
