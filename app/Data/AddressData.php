<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AddressType;
use App\Support\Addresses\AddressFormatter;
use Spatie\LaravelData\Data;

final class AddressData extends Data
{
    public function __construct(
        public AddressType $type,
        public string $line1,
        public ?string $line2 = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $postal_code = null,
        public string $country_code = 'US',
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $label = null,
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload, ?AddressType $fallbackType = null): self
    {
        $type = $payload['type'] ?? $fallbackType ?? AddressType::OTHER;
        $type = is_string($type) ? AddressType::tryFrom($type) ?? AddressType::OTHER : $type;

        $countryCode = strtoupper((string) ($payload['country_code'] ?? $payload['country'] ?? config('address.default_country', 'US')));

        return new self(
            type: $type instanceof AddressType ? $type : AddressType::OTHER,
            line1: trim((string) ($payload['line1'] ?? $payload['street'] ?? '')),
            line2: self::nullableString($payload['line2'] ?? $payload['street2'] ?? null),
            city: self::nullableString($payload['city'] ?? null),
            state: self::nullableString($payload['state'] ?? $payload['province'] ?? null),
            postal_code: self::nullableString($payload['postal_code'] ?? $payload['postal'] ?? $payload['zip'] ?? null),
            country_code: $countryCode,
            latitude: self::nullableFloat($payload['latitude'] ?? null),
            longitude: self::nullableFloat($payload['longitude'] ?? null),
            label: self::nullableString($payload['label'] ?? null),
        );
    }

    public function toLegacyArray(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'street' => $this->line1,
            'street2' => $this->line2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'label' => $this->label,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    public function formatted(?AddressFormatter $formatter = null, bool $multiline = false): string
    {
        return ($formatter ?? new AddressFormatter)->format($this, $multiline);
    }

    public function withCoordinates(?float $latitude, ?float $longitude): self
    {
        return new self(
            type: $this->type,
            line1: $this->line1,
            line2: $this->line2,
            city: $this->city,
            state: $this->state,
            postal_code: $this->postal_code,
            country_code: $this->country_code,
            latitude: $latitude,
            longitude: $longitude,
            label: $this->label,
        );
    }

    public function isEmpty(): bool
    {
        return $this->line1 === '' && $this->city === null && $this->country_code === '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toStorageArray(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country_code' => strtoupper($this->country_code),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'label' => $this->label,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
