# Bag Value Objects

Use [Bag](https://laravel-hub.com/blog/bag-immutable-value-objects-for-modern-php) (`beacon-hq/bag`) to model structured CRM data as immutable, strongly typed value objects instead of passing arrays around. Bag gives us:

- Immutable, typed DTOs with automatic casting (scalars, enums, dates, nested Bags)
- First-class validation via attributes or `rules()`
- Easy composition (`Bag::from()` handles nested Bags and collections)
- Laravel-friendly behaviour (`toArray()`, JSONable, Eloquent casting, controller injection support)

## Patterns to follow

1) **Define a Bag class** with typed promoted properties and validation attributes:

```php
use App\Support\ValueObjects\ContactDetailsBag;
use App\Support\ValueObjects\ContactAddressBag;

$contact = ContactDetailsBag::from([
    'first_name' => 'Ada',
    'last_name' => 'Lovelace',
    'email' => 'ada@example.test',
    'job_title' => 'CTO',
    'address' => [
        'line1' => '123 Market St',
        'city' => 'San Francisco',
        'state' => 'CA',
        'postal_code' => '94105',
        'country_code' => 'US',
    ],
]);

$contact->fullName(); // "Ada Lovelace"
$contact->initials(); // "AL"
$contact->address?->formatted(); // "123 Market St, San Francisco CA 94105, US"
```

2) **Keep immutability**: use `$bag->with(...)` to return a new instance instead of mutating state:

```php
$updated = $contact->with(email: 'ada+updated@example.test');
```

3) **Prefer validation attributes + `rules()`** on each Bag to mirror Laravel validation rules (e.g. `#[Required]`, `#[Email]`, `min`, `size`). Bag validates automatically on `::from()`, so failed input throws a `ValidationException`.

4) **Compose Bags**: nested arrays are cast to their Bag types (e.g. `ContactDetailsBag` → `ContactAddressBag`). Use helper methods (`fullName()`, `initials()`, `toAddressData()`, `formatted()`) to interop with existing services like `AddressFormatter`/`AddressData`.

## Bag classes available

- `App\Support\ValueObjects\ContactDetailsBag` – first/last name, email, phone, job title, optional `ContactAddressBag`, helpers for full name/initials, immutability via `with()`.
- `App\Support\ValueObjects\ContactAddressBag` – normalized country code, type awareness, `toAddressData()` bridge to existing address flows, `formatted()` convenience output.

## Implementation checklist

- Reach for Bag for any new structured payloads (API DTOs, request/response mapping, job payloads) instead of plain arrays.
- Keep constructors small and typed; add helper methods for common display/interop needs.
- Validate at the edge (attributes + `rules()`), then pass Bags through services—no extra casting in business logic.
- When persisting addresses, call `toAddressData()` to reuse the existing formatter, validation, and storage shapes.
