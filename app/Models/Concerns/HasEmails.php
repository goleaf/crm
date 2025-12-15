<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;

trait HasEmails
{
    /**
     * @return Attribute<Collection<int, string>, array<int, string>>
     */
    protected function emails(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): Collection => collect(is_array($value) ? $value : ($this->attributes['emails'] ?? []))
                ->filter()
                ->map(fn (string $email): string => mb_strtolower(trim($email)))
                ->unique()
                ->values(),
            set: fn (mixed $value): array => collect($value ?? [])
                ->filter()
                ->map(fn (string $email): string => mb_strtolower(trim($email)))
                ->unique()
                ->values()
                ->all(),
        );
    }

    public function primaryEmail(): ?string
    {
        /** @var Collection<int, string> $emails */
        $emails = $this->emails;

        return $emails->first();
    }
}
