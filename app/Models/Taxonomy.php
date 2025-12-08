<?php

declare(strict_types=1);

namespace App\Models;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy as BaseTaxonomy;
use App\Models\Concerns\HasTeam;
use App\Services\Tenancy\CurrentTeamResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Taxonomy extends BaseTaxonomy
{
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'slug',
        'type',
        'description',
        'parent_id',
        'sort_order',
        'lft',
        'rgt',
        'depth',
        'meta',
    ];

    private static ?int $slugTeamContext = null;

    protected static function boot(): void
    {
        self::creating(function (self $taxonomy): void {
            self::$slugTeamContext = $taxonomy->team_id ?? CurrentTeamResolver::resolveId();
        });

        parent::boot();

        $reset = static function (): void {
            self::$slugTeamContext = null;
        };

        self::created($reset);
        self::updated($reset);
        self::deleted($reset);
        self::restored($reset);
    }

    public static function slugExists(string $slug, string|\Aliziodev\LaravelTaxonomy\Enums\TaxonomyType $type, ?int $excludeId = null): bool
    {
        $typeValue = $type instanceof \Aliziodev\LaravelTaxonomy\Enums\TaxonomyType ? $type->value : $type;
        $teamId = self::$slugTeamContext ?? CurrentTeamResolver::resolveId();

        /** @var Builder<Model&SoftDeletes> $query */
        $query = self::query();
        $query = config('taxonomy.slugs.consider_trashed', false) ? $query->withTrashed() : $query->withoutTrashed();

        return $query
            ->where('slug', $slug)
            ->where('type', $typeValue)
            ->when($teamId !== null, fn (Builder $builder): Builder => $builder->where('team_id', $teamId))
            ->when($excludeId, fn (Builder $builder): Builder => $builder->whereKeyNot($excludeId))
            ->exists();
    }
}
