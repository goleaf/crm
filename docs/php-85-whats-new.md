# PHP 8.5 Highlights (Preview)

## Overview
- PHP 8.5 is slated for **2025-11-20**. Our current platform targets PHP 8.4; these notes capture upcoming features so we can plan upgrades and coding patterns.
- When experimenting locally, guard new syntax behind `PHP_VERSION_ID >= 80500` and keep Rector/Pint configs aligned once we officially bump the platform version.

## Key Features (from Laravel Hub article)
- **Pipe operator (`|>`)**: Thread a value through transforms without temp variables or nested calls:
  ```php
  $email = '  jane.doe@example.com  '
      |> trim(...)
      |> strtolower(...)
      |> ucfirst(...);
  ```
- **Final property promotion**: Mark promoted constructor properties `final` to keep them immutable:
  ```php
  class ApiRequest
  {
      public function __construct(
          final string $endpoint,
          final array $payload,
      ) {}
  }
  ```
- **CLI diff flag**: `php --ini=diff` shows only overrides versus defaults (handy for debugging local/CI drift).
- **`#[\NoDiscard]` attribute**: Enforce that a return value is consumed:
  ```php
  #[\NoDiscard('Check the result before proceeding')]
  function createTransaction(): TransactionResult { /* ... */ }
  ```
- **Closure/callable constants**: Constants may now hold closures or callable strings for reusable behavior:
  ```php
  class Formatter
  {
      public const TO_UPPER = fn (string $value): string => strtoupper($value);
      public const LOG = self::class.'::log';
  }
  ```
- **`PHP_BUILD_DATE`**: Inspect build timestamp to trace which binary is running.
- **`array_first()` / `array_last()`**: Retrieve boundary elements without pointer side effects.
- **`IntlListFormatter` & locale direction helpers**: Format lists per locale and detect RTL text (e.g., `Locale::isRightToLeft('ar_EG')`).
- **Handler inspection**: `get_exception_handler()` and `get_error_handler()` expose the current handlers for chaining or diagnostics.
- **Bonus**: `curl_multi_get_handles()`, improved fatal stack traces, attributes on constants, and asymmetric visibility support.

## Adoption Tips for This CRM
- Prefer existing helpers (`ArrayHelper`, `StringHelper`) with the pipe operator to keep transformations explicit while leveraging shared utilities.
- Keep service constructors lean and immutable with `final` promoted properties to match our container patterns.
- Add `#[\NoDiscard]` sparingly on side-effect-free methods where ignoring the result is a real bug (e.g., verification/validation outcomes).
- When we raise the platform version, update Rector sets and the minimum PHP constraint in `composer.json`, then run `composer lint` to normalize syntax.***
