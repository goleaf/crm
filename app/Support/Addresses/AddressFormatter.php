<?php

declare(strict_types=1);

namespace App\Support\Addresses;

use App\Data\AddressData;
use Illuminate\Support\Collection;

final class AddressFormatter
{
    /**
     * @var array<string, string>
     */
    private const array COUNTRY_FORMATS = [
        'US' => 'city_state_postal',
        'CA' => 'city_state_postal',
        'AU' => 'city_state_postal',
        'NZ' => 'city_state_postal',
        'GB' => 'city_postal',
        'IE' => 'city_postal',
        'FR' => 'postal_city',
        'DE' => 'postal_city',
        'NL' => 'postal_city',
        'ES' => 'postal_city',
        'IT' => 'postal_city',
        'JP' => 'postal_city_state',
        'SG' => 'city_postal',
        'BR' => 'city_state_postal',
    ];

    public function format(AddressData|array|null $address, bool $multiline = false): string
    {
        if ($address === null) {
            return '—';
        }

        if (is_array($address)) {
            $address = AddressData::fromArray($address);
        }

        $lines = $this->buildLines($address);

        if ($lines->isEmpty()) {
            return '—';
        }

        $separator = $multiline ? PHP_EOL : ', ';

        return $lines->implode($separator);
    }

    private function buildLines(AddressData $address): Collection
    {
        $lines = collect();

        $streetLine = collect([$address->line1, $address->line2])
            ->filter(static fn (mixed $value): bool => filled($value))
            ->implode(', ');

        if ($streetLine !== '') {
            $lines->push($streetLine);
        }

        $cityLine = $this->formatCityLine($address);

        if ($cityLine !== '') {
            $lines->push($cityLine);
        }

        $countryLabel = $this->countryLabel($address->country_code);

        if ($countryLabel !== null) {
            $lines->push($countryLabel);
        }

        return $lines;
    }

    private function formatCityLine(AddressData $address): string
    {
        $countryCode = strtoupper($address->country_code);
        $format = self::COUNTRY_FORMATS[$countryCode] ?? 'city_state_postal';

        $city = trim($address->city ?? '');
        $state = trim($address->state ?? '');
        $postal = trim($address->postal_code ?? '');

        return match ($format) {
            'postal_city' => collect([$postal, $city])
                ->filter(static fn (string $value): bool => $value !== '')
                ->implode(' '),
            'city_postal' => collect([$city, $postal])
                ->filter(static fn (string $value): bool => $value !== '')
                ->implode(' '),
            'postal_city_state' => collect([$postal, $city, $state])
                ->filter(static fn (string $value): bool => $value !== '')
                ->implode(' '),
            default => $this->formatDefaultCityLine($city, $state, $postal),
        };
    }

    private function formatDefaultCityLine(string $city, string $state, string $postal): string
    {
        $rightSide = collect([$state, $postal])
            ->filter(static fn (string $value): bool => $value !== '')
            ->implode(' ');

        return collect([$city, $rightSide])
            ->filter(static fn (string $value): bool => $value !== '')
            ->implode(', ');
    }

    private function countryLabel(string $countryCode): ?string
    {
        $code = strtoupper($countryCode);
        $countries = config('address.countries', []);

        if ($code === '' && $countries === []) {
            return null;
        }

        return $countries[$code] ?? $code;
    }
}
