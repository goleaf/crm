<?php

declare(strict_types=1);

namespace App\Support\Addresses;

use App\Data\AddressData;
use App\Enums\AddressType;
use App\Rules\CleanContent;
use Illuminate\Validation\Rule;
use Intervention\Validation\Rules\Postalcode;

final class AddressValidator
{
    /**
     * @param array<string, mixed> $address
     */
    public function validate(array $address): AddressData
    {
        $normalized = $this->normalizeInput($address);

        $validated = validator($normalized, $this->rules($normalized['country_code']))->validate();

        return new AddressData(
            type: is_string($validated['type']) ? AddressType::tryFrom($validated['type']) ?? AddressType::OTHER : $validated['type'],
            line1: $validated['line1'],
            line2: $validated['line2'] ?? null,
            city: $validated['city'] ?? null,
            state: $validated['state'] ?? null,
            postal_code: $validated['postal_code'] ?? null,
            country_code: strtoupper((string) $validated['country_code']),
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            label: $validated['label'] ?? null,
        );
    }

    /**
     * @param array<int, array<string, mixed>> $addresses
     *
     * @return list<AddressData>
     */
    public function validateMany(array $addresses): array
    {
        return collect($addresses)
            ->map(fn (array $address): AddressData => $this->validate($address))
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(?string $countryCode = null): array
    {
        $country = strtolower($countryCode ?? config('address.default_country', 'US'));

        return [
            'type' => ['required', Rule::enum(AddressType::class)],
            'line1' => ['required', 'string', 'max:255', new CleanContent],
            'line2' => ['nullable', 'string', 'max:255', new CleanContent],
            'city' => ['nullable', 'string', 'max:255', new CleanContent],
            'state' => ['nullable', 'string', 'max:255', new CleanContent],
            'postal_code' => ['nullable', 'string', 'max:20', new Postalcode([$country])],
            'country_code' => ['required', 'string', 'size:2'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'label' => ['nullable', 'string', 'max:120', new CleanContent],
        ];
    }

    /**
     * @param array<string, mixed> $address
     *
     * @return array<string, mixed>
     */
    private function normalizeInput(array $address): array
    {
        $country = strtoupper((string) ($address['country_code'] ?? $address['country'] ?? config('address.default_country', 'US')));

        return [
            'type' => $address['type'] ?? AddressType::OTHER->value,
            'line1' => $address['line1'] ?? $address['street'] ?? '',
            'line2' => $address['line2'] ?? $address['street2'] ?? null,
            'city' => $address['city'] ?? null,
            'state' => $address['state'] ?? $address['province'] ?? null,
            'postal_code' => $address['postal_code'] ?? $address['postal'] ?? $address['zip'] ?? null,
            'country_code' => $country,
            'latitude' => $address['latitude'] ?? null,
            'longitude' => $address['longitude'] ?? null,
            'label' => $address['label'] ?? null,
        ];
    }
}
