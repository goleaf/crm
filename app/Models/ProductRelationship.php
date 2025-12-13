<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductRelationshipType;
use App\Models\Concerns\HasTeam;
use App\Services\Tenancy\CurrentTeamResolver;
use Database\Factories\ProductRelationshipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string     $relationship_type
 * @property int        $priority
 * @property int        $quantity
 * @property float|null $price_override
 * @property bool       $is_required
 */
final class ProductRelationship extends Model
{
    /** @use HasFactory<ProductRelationshipFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'product_id',
        'related_product_id',
        'relationship_type',
        'priority',
        'quantity',
        'price_override',
        'is_required',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'relationship_type' => ProductRelationshipType::class,
            'priority' => 'integer',
            'quantity' => 'integer',
            'price_override' => 'decimal:2',
            'is_required' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function relatedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }

    /**
     * Validate upsell price constraint.
     */
    public function validateUpsellPrice(): bool
    {
        if ($this->relationship_type !== ProductRelationshipType::UPSELL) {
            return true;
        }

        $sourceProduct = $this->product;
        $upsellProduct = $this->relatedProduct;

        if (!$sourceProduct || !$upsellProduct) {
            return false;
        }

        // Use price_override if set, otherwise use the product's price
        $upsellPrice = $this->price_override ?? $upsellProduct->price;
        
        return $upsellPrice >= $sourceProduct->price;
    }

    protected static function booted(): void
    {
        self::creating(function (self $relationship): void {
            $relationship->team_id ??= $relationship->product?->team_id
                ?? Product::withoutGlobalScopes()->whereKey($relationship->product_id)->value('team_id')
                ?? CurrentTeamResolver::resolveId();
        });

        self::saving(function (self $relationship): void {
            $relationship->team_id ??= $relationship->product?->team_id
                ?? Product::withoutGlobalScopes()->whereKey($relationship->product_id)->value('team_id')
                ?? CurrentTeamResolver::resolveId();

            // Validate upsell price constraint
            if ($relationship->relationship_type === ProductRelationshipType::UPSELL->value && !$relationship->validateUpsellPrice()) {
                throw new \InvalidArgumentException('Upsell product price must be greater than or equal to the source product price.');
            }
        });
    }
}
