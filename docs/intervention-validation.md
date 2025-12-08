# Intervention Validation Integration

**Date:** 2025-12-08  
**Component:** Validation & Filament inputs  
**Package:** `intervention/validation` (^4.6.1)

## What changed
- Installed Intervention Validation for 30+ extra rules (postal codes, slug, username, etc.) with automatic Laravel discovery.
- Replaced the custom regex-based postal code rule with `Intervention\Validation\Rules\Postalcode`, normalizing country codes to lowercase for validation while still storing uppercase.
- Applied country-aware postal validation to address capture in Company and Account Filament forms plus the shared `AddressValidator`.
- Enforced Intervention's `slug` rule across knowledge/product categories, knowledge tags, knowledge articles, and product attribute/category relation forms.
- Portal usernames now honor the `username` rule to prevent invalid characters.
- Removed the legacy `postal_code_patterns` config block; the package ships its own datasets and messages.

## Usage patterns
- Manual validation:
  ```php
  use Intervention\Validation\Rules\Postalcode;

  validator($data, [
      'postal_code' => ['nullable', new Postalcode([strtolower($data['country_code'])])],
      'slug' => ['required', 'slug'],
      'username' => ['nullable', 'username'],
  ])->validate();
  ```
- Filament address fields (example):
  ```php
  TextInput::make('postal_code')
      ->maxLength(20)
      ->rules([
          'nullable',
          fn (Get $get): Postalcode => new Postalcode([
              strtolower((string) ($get('country_code') ?? config('address.default_country', 'US'))),
          ]),
      ]);
  ```

## Operational notes
- Expect ISO 3166-1 alpha-2 country codes; validation normalizes to lowercase before passing to the rule.
- Package-provided translations are used for error messages; no local regex maintenance required.
- Tests: `tests/Unit/Validation/PostalcodeRuleTest.php` covers postal, slug, and username rule availability.
