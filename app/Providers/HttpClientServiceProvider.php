<?php

declare(strict_types=1);

namespace App\Providers;

use Closure;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

final class HttpClientServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $provider = $this;

        Http::macro('external', function (?string $service = null, ?string $baseUrl = null) use ($provider): PendingRequest {
            $defaults = config('http-clients.defaults', []);
            $serviceConfig = $service ? config("http-clients.services.{$service}", []) : [];

            if ($baseUrl !== null) {
                $serviceConfig['base_url'] = $baseUrl;
            }

            return $provider->buildPendingRequest($defaults, $serviceConfig);
        });

        Http::macro('github', function () use ($provider): PendingRequest {
            $defaults = config('http-clients.defaults', []);
            $serviceConfig = config('http-clients.services.github', []);

            $serviceConfig['headers'] = array_merge(
                ['Accept' => 'application/vnd.github+json'],
                $serviceConfig['headers'] ?? [],
            );

            $serviceConfig['base_url'] ??= 'https://api.github.com';

            return $provider->buildPendingRequest($defaults, $serviceConfig);
        });
    }

    private function buildPendingRequest(array $defaults, array $serviceConfig): PendingRequest
    {
        $request = Http::acceptJson()->asJson();

        $baseUrl = $serviceConfig['base_url'] ?? $defaults['base_url'] ?? null;
        if ($baseUrl) {
            $request = $request->baseUrl($baseUrl);
        }

        $timeout = $serviceConfig['timeout'] ?? $defaults['timeout'] ?? null;
        if ($timeout !== null) {
            $request = $request->timeout((float) $timeout);
        }

        $connectTimeout = $serviceConfig['connect_timeout'] ?? $defaults['connect_timeout'] ?? null;
        if ($connectTimeout !== null) {
            $request = $request->connectTimeout((float) $connectTimeout);
        }

        $headers = array_merge($defaults['headers'] ?? [], $serviceConfig['headers'] ?? []);
        if ($headers !== []) {
            $request = $request->withHeaders($headers);
        }

        $userAgent = $serviceConfig['user_agent'] ?? $defaults['user_agent'] ?? $this->defaultUserAgent();
        if ($userAgent) {
            $request = $request->withUserAgent($userAgent);
        }

        if (! empty($serviceConfig['token'])) {
            $request = $request->withToken($serviceConfig['token']);
        }

        $retryConfig = array_merge($defaults['retry'] ?? [], $serviceConfig['retry'] ?? []);
        $retryTimes = (int) ($retryConfig['times'] ?? 0);
        $retrySleep = (int) ($retryConfig['sleep_ms'] ?? 100);

        if ($retryTimes > 0) {
            return $request->retry(
                $retryTimes,
                $retrySleep,
                $this->retryDecider(),
                throw: false,
            );
        }

        return $request;
    }

    private function defaultUserAgent(): string
    {
        $brand = brand_name();
        $appUrl = config('app.url');

        return trim($brand.' HTTP Client'.($appUrl ? " ({$appUrl})" : ''));
    }

    private function retryDecider(): Closure
    {
        return static function (Exception $exception): bool {
            if ($exception instanceof ConnectionException) {
                return true;
            }

            if ($exception instanceof RequestException) {
                $status = $exception->response?->status();

                return $status !== null && ($status >= 500 || $status === 429);
            }

            return false;
        };
    }
}
