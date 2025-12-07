<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;

trait HasPhones
{
    /**
     * @return Attribute<Collection<int, string>, array<int, string>>
     */
    protected function phones(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): Collection => collect(is_array($value) ? $value : ($this->attributes['phones'] ?? []))
                ->filter()
                ->map(fn (string $phone): string => trim($phone))
                ->unique()
                ->values(),
            set: fn (mixed $value): array => collect($value ?? [])
                ->filter()
                ->map(fn (string $phone): string => trim($phone))
                ->unique()
                ->values()
                ->all(),
        );
    }

    public function primaryPhone(): ?string
    {
        /** @var Collection<int, string> $phones */
        $phones = $this->phones;

        return $phones->first();
    }
}
