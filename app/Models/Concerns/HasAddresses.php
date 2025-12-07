<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Data\AddressData;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;

trait HasAddresses
{
    /**
     * Normalize addresses into AddressData collection for storage and access.
     *
     * @return Attribute<Collection<int, AddressData>, Collection<int, AddressData>>
     */
    protected function addresses(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): Collection {
                $payload = is_array($value) ? $value : ($this->attributes['addresses'] ?? []);

                return collect($payload)
                    ->filter(fn (mixed $item): bool => is_array($item) || $item instanceof AddressData)
                    ->map(fn (mixed $item): AddressData => $item instanceof AddressData ? $item : AddressData::fromArray($item));
            },
            set: fn (mixed $value): array => collect($value ?? [])
                ->filter(fn (mixed $item): bool => $item instanceof AddressData || is_array($item))
                ->map(fn (mixed $item): mixed => $item instanceof AddressData ? $item->toStorageArray() : $item)
                ->values()
                ->all(),
        );
    }

    /**
     * @return Collection<int, AddressData>
     */
    public function primaryAddresses(): Collection
    {
        /** @var Collection<int, AddressData> $addresses */
        $addresses = $this->addresses;

        return $addresses;
    }
}
