<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use App\Models\Concerns\LogsActivity;
use Database\Factories\ProductCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $team_id
 */
final class ProductCategory extends Model
{
    /** @use HasFactory<ProductCategoryFactory> */
    use HasFactory;

    use HasTeam;
    use LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'parent_id',
        'name',
        'slug',
        'description',
    ];

    /**
     * @return BelongsToMany<Product>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product');
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<ProductCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function getBreadcrumbAttribute(): string
    {
        $segments = [$this->name];
        $current = $this->parent;

        while ($current !== null) {
            $segments[] = $current->name;
            $current = $current->parent;
        }

        return implode(' / ', array_reverse($segments));
    }

    protected static function booted(): void
    {
        self::creating(function (self $category): void {
            if ($category->team_id === null && auth('web')->check()) {
                $category->team_id = auth('web')->user()?->currentTeam?->getKey();
            }

            $category->slug ??= self::generateUniqueSlug($category->name ?? '', $category->team_id);
        });

        self::saving(function (self $category): void {
            $category->slug ??= self::generateUniqueSlug($category->name ?? '', $category->team_id);
        });
    }

    private static function generateUniqueSlug(string $name, ?int $teamId): string
    {
        $baseSlug = Str::slug($name) ?: Str::random(6);
        $slug = $baseSlug;
        $suffix = 1;
        $team = $teamId ?? 0;

        while (self::where('team_id', $team)->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
