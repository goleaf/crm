<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

describe('Home page', function (): void {
    it('returns a successful response', function (): void {
        $response = $this->get('/');

        expect($response)
            ->toBeOk()
            ->toContainText(brand_name());
    });

    it('displays the GitHub stars count', function (): void {
        $testOwner = 'TestOwner';
        $testRepo = 'testrepo';
        config([
            'laravel-crm.ui.github_owner' => $testOwner,
            'laravel-crm.ui.github_repo' => $testRepo,
        ]);

        Http::fake([
            "api.github.com/repos/{$testOwner}/{$testRepo}" => Http::response([
                'stargazers_count' => 125,
            ], 200),
        ]);

        $response = $this->get('/');

        expect($response)
            ->toBeOk()
            ->toContainText('125');
    });
});

describe('Legal pages', function (): void {
    it('displays the terms of service page', function (): void {
        $response = $this->get('/terms-of-service');

        expect($response)
            ->toBeOk()
            ->toContainText('Terms of Service');
    });

    it('displays the privacy policy page', function (): void {
        $response = $this->get('/privacy-policy');

        expect($response)
            ->toBeOk()
            ->toContainText('Privacy Policy');
    });
});

describe('Documentation pages', function (): void {
    it('displays the documentation index', function (): void {
        $response = $this->get('/documentation');

        expect($response)
            ->toBeOk()
            ->toContainText('Documentation');
    });

    it('renders the SuiteCRM feature catalog', function (): void {
        $response = $this->get('/documentation/suitecrm-features');

        expect($response)
            ->toBeOk()
            ->toContainTextInOrder([
                'SuiteCRM Features',
                'Core CRM Modules',
            ]);
    });

    it('returns 404 for non-existent documentation page', function (): void {
        $response = $this->get('/documentation/non-existent-page');

        expect($response)->toBeNotFound();
    });

    it('can search documentation', function (): void {
        $response = $this->get('/documentation/search?query=test');

        expect($response)->toBeOk();
    });
});

describe('Authentication redirects', function (): void {
    it('redirects login to app subdomain', function (): void {
        $response = $this->get('/login');

        expect($response)->toBeRedirect(url()->getAppUrl('login'));
    });

    it('redirects register to app subdomain', function (): void {
        $response = $this->get('/register');

        expect($response)->toBeRedirect(url()->getAppUrl('register'));
    });

    it('redirects forgot password to app subdomain', function (): void {
        $response = $this->get('/forgot-password');

        expect($response)->toBeRedirect(url()->getAppUrl('forgot-password'));
    });

    it('redirects dashboard to app subdomain', function (): void {
        $response = $this->get('/dashboard');

        $expectedUrl = rtrim(url()->getAppUrl(), '/');
        expect($response)->toBeRedirect($expectedUrl);
    });
});

describe('Community redirects', function (): void {
    it('redirects to discord', function (): void {
        config(['services.discord.invite_url' => 'https://discord.gg/example']);

        $response = $this->get('/discord');

        expect($response)->toBeRedirect('https://discord.gg/example');
    });
});

describe('Social authentication routes', function (): void {
    it('throttles authentication redirect attempts', function (): void {
        // Make 10 requests (the limit)
        for ($i = 0; $i < 10; $i++) {
            $this->get('/auth/redirect/github');
        }

        // The 11th request should be throttled
        $response = $this->get('/auth/redirect/github');

        expect($response)->toHaveStatus(429);
    });

    it('accepts github as a provider for redirect', function (): void {
        $response = $this->get('/auth/redirect/github');

        expect($response)->toBeRedirect();
    });

    it('accepts google as a provider for redirect', function (): void {
        $response = $this->get('/auth/redirect/google');

        expect($response)->toBeRedirect();
    });
});

describe('Error handling', function (): void {
    it('returns 404 for non-existent routes', function (): void {
        $response = $this->get('/non-existent-page');

        expect($response)->toBeNotFound();
    });
});

describe('Response meta', function (): void {
    it('returns proper content type', function (): void {
        $response = $this->get('/');

        expect($response)
            ->toHaveHeader('Content-Type')
            ->toBeSuccessful();
    });
});
