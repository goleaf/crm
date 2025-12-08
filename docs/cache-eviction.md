# Cache Eviction Tasks

**Date:** 2025-12-07  
**Component:** Cache maintenance (database/file stores)  
**Package:** `vectorial1024/laravel-cache-evict` (evicts expired items without clearing active cache entries)

## What changed
- Added the Laravel Cache Evict package so expired entries in file/database caches can be removed without flushing framework caches.
- Scheduled automated eviction in `bootstrap/app.php` for every cache store that uses the `database` or `file` drivers (default store plus any additional configured stores).
- Each scheduled run is hourly, non-overlapping, and run in the background to avoid delaying other tasks.

## How to run it manually
- Evict the default cache store:
  ```bash
  php artisan cache:evict
  ```
- Evict a specific cache store defined in `config/cache.php` (example for the file store):
  ```bash
  php artisan cache:evict file
  ```

## Operational notes
- Drivers with built-in eviction (e.g., Redis, Memcached) are intentionally skipped by the scheduler.
- If the default cache store changes to a supported driver, it is picked up automatically by the scheduler; unsupported stores are ignored safely.
- Keep this aligned with the System & Technical requirements for performance (cache housekeeping without full cache clears).
