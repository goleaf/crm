<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsePolicy(\App\Policies\ProductAttributePolicy::class)]
final class ProductAttribute extends Model
{
    /** @use HasFactory<\Database\Factories\ProductAttributeFactory> */
    use HasFactory;

    use HasTeam;
    use HasUniqueSlug;
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ProductAttributeValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Product, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
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
        });
    }
}
