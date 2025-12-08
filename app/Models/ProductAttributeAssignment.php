<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(\App\Policies\ProductAttributeAssignmentPolicy::class)]
final class ProductAttributeAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\ProductAttributeAssignmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'product_attribute_id',
        'product_attribute_value_id',
        'custom_value',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductAttribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    /**
     * @return BelongsTo<ProductAttributeValue, $this>
     */
    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeValue::class, 'product_attribute_value_id');
    }
}
