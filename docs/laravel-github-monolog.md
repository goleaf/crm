# Laravel GitHub Issues Integration

## Overview

This integration automatically creates GitHub issues from application exceptions and logs using the `naoray/laravel-github-monolog` package. It also provides a Filament dashboard widget to view recent open issues directly within the admin panel.

## Configuration

The integration is configured in `config/logging.php` and requires the following environment variables:

```dotenv
GITHUB_REPO=username/repository
GITHUB_TOKEN=your-personal-access-token-with-repo-scope
```

The logging channel `github` is defined in `config/logging.php` and should be added to your stack channel if you want to use it alongside other loggers.

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'github'], // Add 'github' here
    ],
    // ...
]
```

## Features

- **Automatic Issue Creation**: Exceptions and logs (level `debug` and above by default) create issues.
- **De-duplication**: Repeated errors map to the same issue, adding comments instead of creating duplicates.
- **Filament Widget**: View the latest 5 open issues on your dashboard.

## Services

### `App\Services\GitHub\GitHubIssuesService`

This service adheres to the [Container Service Pattern](laravel-container-services.md) and handles fetching issues for the UI.

```php
use App\Services\GitHub\GitHubIssuesService;

$service = app(GitHubIssuesService::class);
$issues = $service->getOpenIssues(10);
```

## Troubleshooting

- **No Issues Created**: Check your `GITHUB_TOKEN` permissions and `GITHUB_REPO` format. Ensure your logging level trigger is met.
- **Widget Empty**: If the widget shows "No open issues", check standard logs for any connection errors from the `GitHubIssuesService`.
