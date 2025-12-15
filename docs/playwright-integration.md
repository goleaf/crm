# Playwright Integration

This project uses [hyvor/laravel-playwright](https://github.com/hyvor/laravel-playwright) (formerly hyvor/laravel-e2e) for End-to-End (E2E) testing. This allows us to write tests in TypeScript/JavaScript using Playwright while interacting with the Laravel backend (database, artisan, etc.) seamlessly.

## Setup

1.  **Install dependencies**:
    ```bash
    composer require --dev hyvor/laravel-playwright
    npm install --save-dev @playwright/test
    npx playwright install
    ```

2.  **Configuration**:
    The `playwright.config.ts` file is located in the root directory. It points to `APP_URL` (default: `http://127.0.0.1:8000`).

## Writing Tests

Tests are located in `tests/Playwright`.

### Example Test

```typescript
import { test, expect } from '@playwright/test';
import { php } from 'hyvor/laravel-playwright';

test('basic test', async ({ page }) => {
    // Run PHP code on the server
    const user = await php('App\\Models\\User::factory()->create()');

    // Login (if you have a helper or use the user created)
    await page.goto('/admin/login');
    await page.fill('input[type="email"]', user.email);
    // ...
});
```

## Running Tests

To run E2E tests:

```bash
npm run test:e2e
```

## Integration with Filament

When testing Filament resources:
-   Use `php()` to scaffold data using factories.
-   Use Playwright locators to interact with Filament tables, forms, and actions.
-   Wait for Livewire updates where necessary (though Playwright auto-waiting helps).

## CI/CD

Ensure the CI pipeline installs Playwright browsers:

```bash
npx playwright install --with-deps
```

And runs the tests after building assets and starting the server.
