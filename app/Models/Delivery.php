<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasNotes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Delivery/shipment record placeholder.
 */
final class Delivery extends Model
{
    use HasFactory;
    use HasNotes;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'tracking_number',
        'carrier',
        'status',
        'shipped_at',
        'delivered_at',
        'notes',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
