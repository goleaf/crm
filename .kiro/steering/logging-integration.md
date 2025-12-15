---
inclusion: always
---

- **Logging Integrations**: When integrating external logging or error tracking services (like GitHub Issues, Sentry, or Flare), always encapsulate API interactions within a dedicated Service class (e.g., `GitHubIssuesService`) following the Container Pattern.
- **Environment Variables**: Never hardcode credentials. Use `config/logging.php` or `config/services.php` to map `.env` variables.
- **Filament Integration**: Provide visibility into these external systems via Dashboard Widgets where possible, but ensure they fail gracefully (e.g., using `try/catch` and caching) to not break the admin panel if the external service is down.
- **Log Channels**: Use Laravel's standard logging channels (`config/logging.php`) rather than custom ad-hoc implementations in the `report()` helper.
