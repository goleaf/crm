<?php

declare(strict_types=1);

namespace App\Services\Examples;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Example Email Verification Service
 *
 * This service demonstrates the Laravel container service pattern with:
 * - Constructor injection with readonly properties
 * - External API integration using Http::external()
 * - Proper error handling and logging
 * - Typed return values (DTO pattern)
 * - Configuration-driven setup
 *
 * @see docs/laravel-container-services.md
 * @see docs/laravel-container-implementation-guide.md
 */
final readonly class ExampleEmailVerificationService
{
    /**
     * Create a new email verification service instance.
     *
     * @param  string  $apiKey  API key for the verification service
     * @param  string  $apiUrl  Base URL for the verification API
     * @param  int  $timeout  Request timeout in seconds
     */
    public function __construct(
        private string $apiKey,
        private string $apiUrl,
        private int $timeout = 10
    ) {}

    /**
     * Verify an email address.
     *
     * @param  string  $email  The email address to verify
     * @return EmailVerificationResult The verification result
     */
    public function verify(string $email): EmailVerificationResult
    {
        try {
            $response = Http::external()
                ->timeout($this->timeout)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get("{$this->apiUrl}/verify", ['email' => $email]);

            if ($response->failed()) {
                Log::warning('Email verification API failed', [
                    'email' => $email,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return new EmailVerificationResult(
                    isValid: false,
                    isDisposable: false,
                    error: 'API request failed'
                );
            }

            $data = $response->json();

            return new EmailVerificationResult(
                isValid: $data['valid'] ?? false,
                isDisposable: $data['disposable'] ?? false
            );

        } catch (\Exception $e) {
            Log::error('Email verification exception', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new EmailVerificationResult(
                isValid: false,
                isDisposable: false,
                error: $e->getMessage()
            );
        }
    }

    /**
     * Verify multiple email addresses in batch.
     *
     * @param  array<string>  $emails  Array of email addresses to verify
     * @return array<string, EmailVerificationResult> Results keyed by email
     */
    public function verifyBatch(array $emails): array
    {
        $results = [];

        foreach ($emails as $email) {
            $results[$email] = $this->verify($email);
        }

        return $results;
    }

    /**
     * Create service instance from configuration.
     *
     * This static factory method demonstrates how to create a service
     * instance using configuration values, which is useful when registering
     * the service in AppServiceProvider.
     */
    public static function fromConfig(): self
    {
        return new self(
            apiKey: config('services.email_verification.api_key'),
            apiUrl: config('services.email_verification.api_url'),
            timeout: config('services.email_verification.timeout', 10)
        );
    }
}

/**
 * Email Verification Result DTO
 *
 * This readonly class demonstrates the Data Transfer Object (DTO) pattern
 * for returning structured data from services.
 */
final readonly class EmailVerificationResult
{
    /**
     * Create a new email verification result.
     *
     * @param  bool  $isValid  Whether the email is valid
     * @param  bool  $isDisposable  Whether the email is from a disposable domain
     * @param  string|null  $error  Error message if verification failed
     */
    public function __construct(
        public bool $isValid,
        public bool $isDisposable,
        public ?string $error = null
    ) {}

    /**
     * Check if the verification was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->error === null;
    }

    /**
     * Check if the email is acceptable (valid and not disposable).
     */
    public function isAcceptable(): bool
    {
        return $this->isValid && ! $this->isDisposable && $this->isSuccessful();
    }
}
