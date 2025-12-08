# LaraPath integration guide

We use `hdaklue/larapath` to keep every stored path sanitized, validated, and tenant-aware. The package is installed in composer and centralized in `app/Support/Paths/StoragePaths.php`.

## Current patterns
- **PDF generation** (`PdfService`): file names come from `StoragePaths::pdfFileName()` (slugged, timestamped, random) and are stored under a hashed team directory via `StoragePaths::pdfStoragePath()` which validates and checks for conflicts.
- **Document uploads** (Document resource + Versions relation): `StoragePaths::documentsDirectory()` hashes the team folder, and `StoragePaths::documentFileName()` creates a slugged, timestamped file name for both the initial upload and later versions.
- **Profile photos** (profile update Livewire component): files land in `profile-photos/{teamHash}/{userHash}` with names from `StoragePaths::profilePhotoFileName()`, keeping user data private and filenames shell-safe.

## How to use LaraPath in new code
- Prefer `StoragePaths` helpers for any stored path; they wrap LaraPath’s sanitization strategies and hashing so teams/users aren’t exposed in directory names.
- When building custom paths, start from `PathBuilder::base()` and chain `add()/addFile()` with a `SanitizationStrategy` plus `->validate()` before persisting.
- Guard external or user-provided paths with `PathBuilder::isSafe($path)` before reading/deleting from storage.

## Verification
- Automated coverage lives in `tests/Unit/Support/StoragePathsTest.php` to lock the hashed directory layout and slugged filename patterns in place. Update these tests when adjusting path rules.
