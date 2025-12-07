<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Database\Factories\InvoiceLineItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property float $quantity
 * @property float $unit_price
 * @property float $line_total
 * @property float $tax_total
 */
final class InvoiceLineItem extends Model
{
    /** @use HasFactory<InvoiceLineItemFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'team_id',
        'name',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'line_total',
        'tax_total',
        'sort_order',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'line_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (self $lineItem): void {
            if ($lineItem->team_id === null && $lineItem->invoice !== null) {
                $lineItem->team_id = $lineItem->invoice->team_id;
            }

            if ($lineItem->sort_order === 0 && $lineItem->invoice !== null) {
                $lineItem->sort_order = (int) (($lineItem->invoice->lineItems()->max('sort_order') ?? 0) + 1);
            }

            self::calculateTotals($lineItem);
        });

        self::saving(function (self $lineItem): void {
            self::calculateTotals($lineItem);
        });

        self::saved(function (self $lineItem): void {
            $lineItem->invoice?->syncFinancials();
        });
    }

    private static function calculateTotals(self $lineItem): void
    {
        $lineTotal = round(((float) $lineItem->quantity) * ((float) $lineItem->unit_price), 2);
        $taxTotal = round($lineTotal * ((float) $lineItem->tax_rate / 100), 2);

        $lineItem->line_total = $lineTotal;
        $lineItem->tax_total = $taxTotal;
    }
}
