<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\LogsActivity;
use Database\Factories\ProductCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $team_id
 */
final class ProductCategory extends Model
{
    /** @use HasFactory<ProductCategoryFactory> */
    use HasFactory;

    use HasTeam;
    use HasUniqueSlug;
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
        'sort_order',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Product, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
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
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get all ancestors (parent, grandparent, etc.) of this category.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ProductCategory>
     */
    public function ancestors(): \Illuminate\Database\Eloquent\Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current !== null) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Get all descendants (children, grandchildren, etc.) of this category.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ProductCategory>
     */
    public function descendants(): \Illuminate\Database\Eloquent\Collection
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }

        return $descendants;
    }

    /**
     * Get all products in this category and its subcategories.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function allProducts(): \Illuminate\Database\Eloquent\Collection
    {
        $categoryIds = collect([$this->id]);
        $categoryIds = $categoryIds->merge($this->descendants()->pluck('id'));

        return Product::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('product_categories.id', $categoryIds);
        })->get();
    }

    /**
     * Check if this category is an ancestor of the given category.
     */
    public function isAncestorOf(ProductCategory $category): bool
    {
        return $category->ancestors()->contains('id', $this->id);
    }

    /**
     * Check if this category is a descendant of the given category.
     */
    public function isDescendantOf(ProductCategory $category): bool
    {
        return $this->ancestors()->contains('id', $category->id);
    }

    /**
     * Get the depth level of this category in the hierarchy (root = 0).
     */
    public function getDepth(): int
    {
        return $this->ancestors()->count();
    }

    /**
     * Get the root category of this category's hierarchy.
     */
    public function getRoot(): ProductCategory
    {
        $ancestors = $this->ancestors();
        
        return $ancestors->isEmpty() ? $this : $ancestors->last();
    }

    protected function getBreadcrumbAttribute(): string
    {
        $segments = [$this->name];
        $current = $this->parent;

        while ($current !== null) {
            $segments[] = $current->name;
            $current = $current->parent;
        }

        return implode(' / ', array_reverse($segments));
    }

    /**
     * Scope to order categories by sort_order and name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    protected static function booted(): void
    {
        self::creating(function (self $category): void {
            if ($category->team_id === null && auth('web')->check()) {
                $category->team_id = auth('web')->user()?->currentTeam?->getKey();
            }
            
            // Set default sort_order if not provided
            if ($category->sort_order === null) {
                $maxSortOrder = self::where('team_id', $category->team_id)
                    ->where('parent_id', $category->parent_id)
                    ->max('sort_order') ?? 0;
                $category->sort_order = $maxSortOrder + 1;
            }
        });
    }
}
