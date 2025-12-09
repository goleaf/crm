# GraphQL (Lighthouse) Integration

## Overview
We integrate [Lighthouse](https://lighthouse-php.com) to expose a secure GraphQL layer for the CRM API, following the approach highlighted in the Laravel Hub article “Lighthouse: A Simple, Flexible GraphQL Layer for Laravel.” The endpoint is protected by Sanctum and throttled via `throttle:api`.

## Endpoint & Auth
- Endpoint: `POST /graphql`
- Middleware: `api`, `auth:sanctum`, `throttle:api`, `AcceptJson`
- Guards: `sanctum` (see `config/lighthouse.php`)
- Use a Sanctum PAT or session-authenticated request; include `Accept: application/json`.

## Schema & Resolvers
- Schema file: `graphql/schema.graphql`
- Resolvers: `app/GraphQL/Queries`, `app/GraphQL/Mutations`, etc. (see namespaces in `config/lighthouse.php`)
- Health query example (already wired):
  ```graphql
  query {
    health {
      name
      environment
      frameworkVersion
      phpVersion
      timestamp
    }
  }
  ```
- Resolver: `App\GraphQL\Queries\Health`
- Authenticated user example:
  ```graphql
  query {
    me {
      id
      name
      email
    }
  }
  ```
  Resolver: `App\GraphQL\Queries\Me`

## Usage (curl example)
```bash
curl -X POST http://localhost/graphql \
  -H "Authorization: Bearer <sanctum_api_token>" \
  -H "Content-Type: application/json" \
  -d '{"query":"{ health { name environment frameworkVersion phpVersion timestamp } }"}'
```

## Configuration
- Route/security/caching: `config/lighthouse.php`
- Env toggles in `.env.example`:
  - `LIGHTHOUSE_SCHEMA_CACHE_ENABLE`
  - `LIGHTHOUSE_QUERY_CACHE_ENABLE`
  - `LIGHTHOUSE_QUERY_CACHE_TTL`
  - `LIGHTHOUSE_VALIDATION_CACHE_ENABLE`
  - `LIGHTHOUSE_SECURITY_DISABLE_INTROSPECTION`
- Cache commands: `php artisan lighthouse:clear-cache`, `php artisan lighthouse:clear-schema-cache`, `php artisan lighthouse:validate-schema`

## Development Tips
- Keep schema changes in `graphql/schema.graphql` and add resolvers in `app/GraphQL/*`.
- Use field-level auth directives (`@guard`, `@can`) if exposing new types/queries.
- Run `php artisan lighthouse:validate-schema` after schema edits.
