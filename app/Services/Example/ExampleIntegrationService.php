<?php

declare(strict_types=1);

namespace App\Services\Example;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Example integration service demonstrating HTTP client patterns.
 *
 * Integration services communicate with external APIs.
 * Register as singleton and use Http::external() macro.
 */
final readonly class ExampleIntegrationService
{
    public function __construct(
        private string $apiKey,
        private int $timeout = 10,
        private int $retries = 3,
    ) {}

    /**
     * Create service from configuration.
     */
    public static function fromConfig(): self
    {
        return new self(
            apiKey: config('services.example.api_key', ''),
            timeout: (int) config('services.example.timeout', 10),
            retries: (int) config('services.example.retries', 3),
        );
    }

    /**
     * Fetch data from external API with retry logic.
     */
    public function fetchData(string $endpoint, array $params = []): ?array
    {
        try {
            $response = Http::external()
                ->timeout($this->timeout)
                ->retry($this->retries, 100)
                ->withToken($this->apiKey)
                ->get($endpoint, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('External API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('External API exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Post data to external API.
     */
    public function postData(string $endpoint, array $data): bool
    {
        try {
            $response = Http::external()
                ->timeout($this->timeout)
                ->retry($this->retries, 100)
                ->withToken($this->apiKey)
                ->post($endpoint, $data);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('External API post exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
