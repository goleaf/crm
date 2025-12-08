import { test, expect } from '@playwright/test';
import { php } from 'hyvor/laravel-playwright';

test('homepage works', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveTitle(/CRM/);
});

test('can create user via php', async () => {
    const user = await php('App\\Models\\User::factory()->create()');
    expect(user.id).toBeDefined();
});
