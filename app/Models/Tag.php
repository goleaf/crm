<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

final class Tag extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'color',
    ];

    /**
     * @return MorphToMany<Lead, $this>
     */
    public function leads(): MorphToMany
    {
        return $this->morphedByMany(Lead::class, 'taggable');
    }

    /**
     * @return MorphToMany<Opportunity, $this>
     */
    public function opportunities(): MorphToMany
    {
        return $this->morphedByMany(Opportunity::class, 'taggable');
    }

    /**
     * @return MorphToMany<Company, $this>
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, 'taggable');
    }

    /**
     * @return MorphToMany<People, $this>
     */
    public function people(): MorphToMany
    {
        return $this->morphedByMany(People::class, 'taggable');
    }

    protected static function booted(): void
    {
        self::creating(function (self $tag): void {
            if ($tag->team_id === null && auth('web')->check()) {
                $tag->team_id = auth('web')->user()?->currentTeam?->getKey();
            }
        });
    }
}
