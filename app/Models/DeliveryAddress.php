<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeliveryAddressType;
use App\Models\Concerns\HasTeam;
use Database\Factories\DeliveryAddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DeliveryAddress extends Model
{
    /** @use HasFactory<DeliveryAddressFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'delivery_id',
        'team_id',
        'type',
        'sequence',
        'label',
        'contact_name',
        'phone',
        'street',
        'city',
        'state',
        'postal_code',
        'country',
        'instructions',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'type' => DeliveryAddressType::class,
        ];
    }

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    protected static function booted(): void
    {
        self::creating(function (self $address): void {
            if ($address->team_id === null && $address->delivery !== null) {
                $address->team_id = $address->delivery->team_id;
            }
        });
    }
}
