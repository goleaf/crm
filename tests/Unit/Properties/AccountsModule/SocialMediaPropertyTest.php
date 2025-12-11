<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Team;
use App\Models\User;

/**
 * **Feature: accounts-module, Property 33: Social media profile storage**
 *
 * **Validates: Requirements 14.1, 14.2, 14.3**
 *
 * Properties:
 * - For any account with social media profile URLs, the profiles should be stored
 *   in a structured format, retrievable, and displayable with appropriate validation.
 */

// Property 33: Social media profiles are stored in structured format
test('property: social media profiles are stored and retrieved correctly', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    // Generate random social media profiles
    $socialLinks = [];
    $platforms = ['linkedin', 'twitter', 'facebook', 'instagram'];
    $selectedPlatforms = fake()->randomElements($platforms, fake()->numberBetween(1, 4));

    foreach ($selectedPlatforms as $platform) {
        $socialLinks[$platform] = match ($platform) {
            'linkedin' => 'https://linkedin.com/company/' . fake()->slug(),
            'twitter' => 'https://twitter.com/' . fake()->userName(),
            'facebook' => 'https://facebook.com/' . fake()->slug(),
            'instagram' => 'https://instagram.com/' . fake()->userName(),
        };
    }

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'social_links' => $socialLinks,
        ]);

    // Verify social links are persisted correctly
    expect($company->social_links)->toBe($socialLinks);

    // Verify social links are retrievable after fresh query
    $retrieved = Company::find($company->getKey());
    expect($retrieved->social_links)->toBe($socialLinks);

    // Verify each platform URL is accessible
    foreach ($socialLinks as $platform => $url) {
        expect($retrieved->social_links[$platform])->toBe($url);
    }
})->repeat(100);

// Property 33: Social media profiles support multiple platforms
test('property: multiple social media platforms can be stored simultaneously', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    // Create comprehensive social media profile
    $socialLinks = [
        'linkedin' => 'https://linkedin.com/company/' . fake()->slug(),
        'twitter' => 'https://twitter.com/' . fake()->userName(),
        'facebook' => 'https://facebook.com/' . fake()->slug(),
        'instagram' => 'https://instagram.com/' . fake()->userName(),
        'youtube' => 'https://youtube.com/c/' . fake()->slug(),
        'tiktok' => 'https://tiktok.com/@' . fake()->userName(),
    ];

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'social_links' => $socialLinks,
        ]);

    // Verify all platforms are stored
    expect($company->social_links)->toHaveCount(count($socialLinks));

    foreach ($socialLinks as $platform => $url) {
        expect($company->social_links)->toHaveKey($platform);
        expect($company->social_links[$platform])->toBe($url);
    }

    // Verify after fresh query
    $retrieved = Company::find($company->getKey());
    expect($retrieved->social_links)->toBe($socialLinks);
})->repeat(50);

// Property 33: Social media profiles can be updated
test('property: social media profiles can be updated on existing account', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    // Initial social links
    $initialLinks = [
        'linkedin' => 'https://linkedin.com/company/' . fake()->slug(),
        'twitter' => 'https://twitter.com/' . fake()->userName(),
    ];

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'social_links' => $initialLinks,
        ]);

    // Update social links
    $updatedLinks = [
        'linkedin' => 'https://linkedin.com/company/' . fake()->slug(), // Updated
        'twitter' => $initialLinks['twitter'], // Kept same
        'facebook' => 'https://facebook.com/' . fake()->slug(), // Added new
    ];

    $company->update(['social_links' => $updatedLinks]);

    // Verify update persisted
    expect($company->social_links)->toBe($updatedLinks);

    // Verify after fresh query
    $retrieved = Company::find($company->getKey());
    expect($retrieved->social_links)->toBe($updatedLinks);
})->repeat(100);

// Property 33: Social media profiles handle empty/null values correctly
test('property: social media profiles handle empty and null values correctly', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    // Test with null social_links
    $company1 = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'social_links' => null,
        ]);

    expect($company1->social_links)->toBeNull();

    // Test with empty array
    $company2 = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'social_links' => [],
        ]);

    expect($company2->social_links)->toBe([]);

    // Test updating from null to populated
    $socialLinks = [
        'linkedin' => 'https://linkedin.com/company/' . fake()->slug(),
    ];

    $company1->update(['social_links' => $socialLinks]);
    expect($company1->social_links)->toBe($socialLinks);

    // Test updating from populated to null
    $company1->update(['social_links' => null]);
    expect($company1->social_links)->toBeNull();
})->repeat(50);

// Property 33: Social media profiles are preserved through model operations
test('property: social media profiles are preserved through model operations', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $socialLinks = [
        'linkedin' => 'https://linkedin.com/company/' . fake()->slug(),
        'twitter' => 'https://twitter.com/' . fake()->userName(),
        'facebook' => 'https://facebook.com/' . fake()->slug(),
    ];

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'social_links' => $socialLinks,
        ]);

    // Update other fields
    $company->update([
        'name' => fake()->company(),
        'website' => fake()->url(),
        'employee_count' => fake()->numberBetween(10, 1000),
        'description' => fake()->paragraph(),
    ]);

    // Social links should remain unchanged
    expect($company->social_links)->toBe($socialLinks);

    // Refresh and verify
    $company->refresh();
    expect($company->social_links)->toBe($socialLinks);

    // Fresh query and verify
    $retrieved = Company::find($company->getKey());
    expect($retrieved->social_links)->toBe($socialLinks);
})->repeat(100);

// Property 33: Social media profiles are included in model serialization
test('property: social media profiles are included in model serialization', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $socialLinks = [
        'linkedin' => 'https://linkedin.com/company/' . fake()->slug(),
        'twitter' => 'https://twitter.com/' . fake()->userName(),
    ];

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'social_links' => $socialLinks,
        ]);

    // Verify social_links is in toArray output
    $array = $company->toArray();
    expect($array)->toHaveKey('social_links')
        ->and($array['social_links'])->toBe($socialLinks);

    // Verify social_links is in JSON output
    $json = json_decode($company->toJson(), true);
    expect($json)->toHaveKey('social_links')
        ->and($json['social_links'])->toBe($socialLinks);
})->repeat(50);

// Property 33: Social media profiles support URL validation format
test('property: social media profiles store valid URL formats', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    // Generate valid URLs for different platforms
    $validUrls = [
        'linkedin' => fake()->randomElement([
            'https://linkedin.com/company/' . fake()->slug(),
            'https://www.linkedin.com/company/' . fake()->slug(),
            'https://linkedin.com/in/' . fake()->userName(),
        ]),
        'twitter' => fake()->randomElement([
            'https://twitter.com/' . fake()->userName(),
            'https://x.com/' . fake()->userName(),
        ]),
        'facebook' => fake()->randomElement([
            'https://facebook.com/' . fake()->slug(),
            'https://www.facebook.com/' . fake()->slug(),
            'https://fb.com/' . fake()->slug(),
        ]),
        'instagram' => fake()->randomElement([
            'https://instagram.com/' . fake()->userName(),
            'https://www.instagram.com/' . fake()->userName(),
        ]),
    ];

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'social_links' => $validUrls,
        ]);

    // Verify all URLs are stored correctly
    foreach ($validUrls as $platform => $url) {
        expect($company->social_links[$platform])->toBe($url);
        // Verify URL format is maintained
        expect($company->social_links[$platform])->toStartWith('https://');
    }

    // Verify after fresh query
    $retrieved = Company::find($company->getKey());
    expect($retrieved->social_links)->toBe($validUrls);
})->repeat(100);

// Property 33: Social media profiles can be partially updated
test('property: individual social media profiles can be updated without affecting others', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $initialLinks = [
        'linkedin' => 'https://linkedin.com/company/' . fake()->slug(),
        'twitter' => 'https://twitter.com/' . fake()->userName(),
        'facebook' => 'https://facebook.com/' . fake()->slug(),
    ];

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'social_links' => $initialLinks,
        ]);

    // Update only one platform
    $updatedLinks = $initialLinks;
    $updatedLinks['twitter'] = 'https://twitter.com/' . fake()->userName();

    $company->update(['social_links' => $updatedLinks]);

    // Verify only twitter was updated, others remain the same
    expect($company->social_links['linkedin'])->toBe($initialLinks['linkedin']);
    expect($company->social_links['facebook'])->toBe($initialLinks['facebook']);
    expect($company->social_links['twitter'])->toBe($updatedLinks['twitter']);
    expect($company->social_links['twitter'])->not->toBe($initialLinks['twitter']);
})->repeat(100);
