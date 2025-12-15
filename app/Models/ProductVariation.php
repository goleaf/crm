<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsePolicy(\App\Policies\ProductVariationPolicy::class)]
final class ProductVariation extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariationFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'currency_code',
        'is_default',
        'track_inventory',
        'inventory_quantity',
        'reserved_quantity',
        'options',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'track_inventory' => false,
        'inventory_quantity' => 0,
        'reserved_quantity' => 0,
        'is_default' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_default' => 'boolean',
            'track_inventory' => 'boolean',
            'inventory_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'options' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function availableInventory(): int
    {
        return max(0, (int) $this->inventory_quantity - (int) $this->reserved_quantity);
    }

    public function getOptionValue(string $attributeSlug): ?string
    {
        $options = $this->options ?? [];

        return $options[$attributeSlug] ?? null;
    }

    protected static function booted(): void
    {
        self::creating(function (self $variation): void {
            $variation->currency_code ??= $variation->product?->currency_code ?? config('company.default_currency', 'USD');
        });
    }
}
