# Name handling with name-of-person

- Dependency `hosmelq/name-of-person` is available for parsing, formatting, and casting human names.
- Use `App\Support\PersonNameFormatter` instead of manual `explode`/`substr` logic. It wraps the package for `full()`, `first()`, `last()`, `familiar()`, `initials()`, and `mentionable()` while handling empty fallbacks.
- Models that store first/last columns now expose a typed name cast:
  - `Employee::$casts['name'] = PersonNameCast::using('first_name', 'last_name')` (accessible via `$employee->name` or `$employee->full_name`).
  - `EmailProgramRecipient::$casts['name'] = PersonNameCast::class` plus a `full_name` accessor; assigning `$recipient->name = new PersonName('Ada', 'Lovelace')` sets both columns.
- UI helpers now rely on the package for initials (avatar service, profile photo fallbacks) and for deriving first/last names when creating email program recipients or personal teams.
- Email-to-case contact creation also uses `PersonNameFormatter::full()` so incoming messages produce consistent contact names and capture the email as a fallback.

## Quick usage

```php
use App\Support\PersonNameFormatter;
use HosmelQ\NameOfPerson\PersonName;

$name = PersonNameFormatter::make('Grace Hopper');

$initials = PersonNameFormatter::initials($name);     // "GH"
$mention  = PersonNameFormatter::mentionable($name);  // "graceh"

$recipient->name = new PersonName('Ada', 'Lovelace'); // fills first_name + last_name via cast
```
