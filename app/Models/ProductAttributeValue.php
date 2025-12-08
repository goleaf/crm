<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(\App\Policies\ProductAttributeValuePolicy::class)]
final class ProductAttributeValue extends Model
{
    /** @use HasFactory<\Database\Factories\ProductAttributeValueFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_attribute_id',
        'value',
        'code',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProductAttribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }
}
