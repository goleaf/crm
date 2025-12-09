<?php

declare(strict_types=1);

namespace App\Support\Addresses;

use App\Data\AddressData;
use Illuminate\Support\Facades\Log;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final readonly class AddressGeocoder
{
    public function __construct(
        private HttpClientInterface $http,
        private AddressFormatter $formatter = new AddressFormatter,
    ) {}

    public function geocode(AddressData $address): AddressData
    {
        $config = config('address.geocoding', []);

        if (! ($config['enabled'] ?? false) || blank($config['endpoint'] ?? null)) {
            return $address;
        }

        $query = $this->formatter->format($address);

        if ($query === '—') {
            return $address;
        }

        try {
            $response = $this->http->request('GET', $config['endpoint'], [
                'query' => array_filter([
                    'q' => $query,
                    'api_key' => $config['api_key'] ?? null,
                    'format' => $config['provider'] === 'nominatim' ? 'json' : null,
                    'limit' => 1,
                ]),
                'timeout' => $config['timeout'] ?? 5,
            ]);

            $payload = $response->toArray(false);
            $coordinates = $this->extractCoordinates($payload);

            return $coordinates !== null
                ? $address->withCoordinates($coordinates['lat'], $coordinates['lng'])
                : $address;
        } catch (Throwable $exception) {
            Log::warning('Address geocoding failed', [
                'exception' => $exception->getMessage(),
            ]);

            return $address;
        }
    }

    /**
     * @param array<string, mixed>|array<int, array<string, mixed>> $payload
     *
     * @return array{lat: float, lng: float}|null
     */
    private function extractCoordinates(array $payload): ?array
    {
        $candidate = $payload;

        if (array_is_list($payload) && isset($payload[0]) && is_array($payload[0])) {
            $candidate = $payload[0];
        }

        $lat = $candidate['lat'] ?? $candidate['latitude'] ?? null;
        $lng = $candidate['lng'] ?? $candidate['lon'] ?? $candidate['longitude'] ?? null;

        if ($lat === null || $lng === null) {
            return null;
        }

        return [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];
    }
}
