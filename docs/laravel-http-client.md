# Laravel HTTP client integration (Filament v4.3+ ready)

## What’s included
- New config at `config/http-clients.php` sets sane defaults: JSON requests, brand-aware user agent, `timeout`/`connect_timeout`, and retry backoff (defaults 2 tries / 200ms).
- Macros:
  - `Http::external(?string $service = null, ?string $baseUrl = null)` builds a JSON client with defaults + optional service overrides.
  - `Http::github()` pins the GitHub API base URL, adds the v4 accept header, merges retry overrides (3 tries / 250ms), and injects a token when `GITHUB_HTTP_TOKEN` is set.
- GitHub stars now use the macro, retrying on 5xx/429/connection errors while respecting cache TTL from config.

## Defaults & environment knobs
- Global overrides: `HTTP_CLIENT_TIMEOUT`, `HTTP_CLIENT_CONNECT_TIMEOUT`, `HTTP_CLIENT_RETRY_TIMES`, `HTTP_CLIENT_RETRY_SLEEP_MS`.
- GitHub-specific: `GITHUB_HTTP_BASE_URL`, `GITHUB_HTTP_TOKEN`, `GITHUB_HTTP_TIMEOUT`, `GITHUB_HTTP_CONNECT_TIMEOUT`, `GITHUB_HTTP_CACHE_MINUTES`, `GITHUB_HTTP_RETRY_TIMES`, `GITHUB_HTTP_RETRY_SLEEP_MS`.
- User agent: `<brand_name> HTTP Client (<app.url>)` so upstreams can identify Filament traffic; set `laravel-crm.ui.brand_name`/`APP_URL` to shape it.

## Usage patterns
```php
// Generic external call
$response = Http::external()->get('https://api.example.com/widgets');

// Service-aware client with base URL and retry rules
$response = Http::external('github')->get('/rate_limit');

// Filament v4.3+ action pulling remote stats
use Filament\Actions\Action;

Action::make('syncRepositories')
    ->requiresConfirmation()
    ->action(function () {
        $response = Http::github()->get('/user/repos');

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'sync' => __('app.errors.github_unavailable'),
            ]);
        }

        // hydrate local models...
    });
```

## Testing guidance
- Fake by URL fragment or glob:  
  `Http::fake(['api.github.com/*' => Http::response([...], 200)]);`
- Use `Http::sequence()` for retry paths; `Http::assertSentCount()` ensures the retry decider fired.
- Prefer `Http::preventStrayRequests()` in tests that shouldn’t hit the network.

## Reference
- Tips adapted from [Laravel HTTP client best practices](https://laravel-news.com/laravel-http-client-tips).
- See `App\Services\GitHubService` for a concrete Filament-facing example (header widget stars).
