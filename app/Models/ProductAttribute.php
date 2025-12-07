<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[UsePolicy(\App\Policies\ProductAttributePolicy::class)]
final class ProductAttribute extends Model
{
    /** @use HasFactory<\Database\Factories\ProductAttributeFactory> */
    use HasFactory;

    use HasTeam;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'slug',
        'data_type',
        'is_configurable',
        'is_filterable',
        'is_required',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_configurable' => 'boolean',
            'is_filterable' => 'boolean',
            'is_required' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ProductAttributeValue>
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * @return BelongsToMany<Product>
     */
    public function configurableForProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_configurable_attributes');
    }

    protected static function booted(): void
    {
        self::creating(function (self $attribute): void {
            if ($attribute->team_id === null && auth('web')->check()) {
                $attribute->team_id = auth('web')->user()?->currentTeam?->getKey();
            }

            $attribute->slug ??= self::generateUniqueSlug($attribute->name ?? '', $attribute->team_id);
        });

        self::saving(function (self $attribute): void {
            $attribute->slug ??= self::generateUniqueSlug($attribute->name ?? '', $attribute->team_id);
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
