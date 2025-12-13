# API Reference

## Overview

This document provides a comprehensive reference for all API endpoints in the Relaticle CRM system.

**Base URL**: `/api/v1`  
**Authentication**: Bearer Token (Sanctum)  
**Content Type**: `application/json`

## Team Management API

### Get Team Information

Retrieve information about the current team (tenant).

**Endpoint**: `GET /api/v1/team`

**Response:**
```json
{
    "data": {
        "id": 1,
        "name": "Acme Corporation",
        "personal_team": false,
        "created_at": "2025-01-01T00:00:00Z",
        "updated_at": "2025-01-01T00:00:00Z",
        "avatar_url": "https://example.com/avatar.png",
        "stats": {
            "people_count": 150,
            "companies_count": 45,
            "tasks_count": 89,
            "opportunities_count": 23,
            "notes_count": 234,
            "leads_count": 67,
            "support_cases_count": 12
        }
    }
}
```

### Update Team Information

Update team details (requires team owner permissions).

**Endpoint**: `PUT /api/v1/team`

**Request Body:**
```json
{
    "name": "Updated Team Name"
}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "name": "Updated Team Name",
        "personal_team": false,
        "updated_at": "2025-12-13T15:30:00Z"
    }
}
```

## Activity Logging API

### Get Model Activities

Retrieve activity history for any model that implements the `LogsActivity` trait.

**Endpoint**: `GET /api/v1/{model}/{id}/activities`

**Parameters:**
- `model` (path) - Model type (companies, people, opportunities, tasks, etc.)
- `id` (path) - Model ID
- `event` (query, optional) - Filter by event type (created, updated, deleted)
- `causer_id` (query, optional) - Filter by user who caused the activity
- `per_page` (query, optional) - Number of results per page (default: 15)

**Response:**
```json
{
    "data": [
        {
            "id": 123,
            "event": "updated",
            "changes": {
                "attributes": {
                    "account_type": "prospect"
                },
                "old": {
                    "account_type": "customer"
                }
            },
            "causer": {
                "id": 456,
                "name": "John Doe",
                "email": "john@example.com"
            },
            "created_at": "2025-12-11T10:30:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 25
    }
}
```

### Activity Change Tracking

All models with the `LogsActivity` trait automatically track:

- **Created Events**: When a new record is created
- **Updated Events**: When fields are modified (includes old/new values)
- **Deleted Events**: When a record is deleted

**Change Format:**
```json
{
    "attributes": {
        "field_name": "new_value"
    },
    "old": {
        "field_name": "old_value"
    }
}
```

## Task Reminders API

### Schedule Task Reminder

Create a new reminder for a task.

**Endpoint**: `POST /api/v1/tasks/{task}/reminders`

**Parameters:**
- `task` (path) - Task ID

**Request Body:**
```json
{
    "remind_at": "2025-12-11T10:00:00Z",
    "channel": "email",
    "user_id": 123
}
```

**Response:**
```json
{
    "data": {
        "id": 456,
        "task_id": 789,
        "user_id": 123,
        "remind_at": "2025-12-11T10:00:00Z",
        "channel": "email",
        "status": "pending",
        "created_at": "2025-12-10T15:30:00Z",
        "updated_at": "2025-12-10T15:30:00Z"
    }
}
```

### Get Task Reminders

Retrieve all reminders for a task.

**Endpoint**: `GET /api/v1/tasks/{task}/reminders`

**Parameters:**
- `task` (path) - Task ID
- `status` (query, optional) - Filter by status (pending, sent, canceled, failed)

**Response:**
```json
{
    "data": [
        {
            "id": 456,
            "task_id": 789,
            "user_id": 123,
            "remind_at": "2025-12-11T10:00:00Z",
            "sent_at": null,
            "canceled_at": null,
            "channel": "email",
            "status": "pending",
            "created_at": "2025-12-10T15:30:00Z",
            "updated_at": "2025-12-10T15:30:00Z",
            "user": {
                "id": 123,
                "name": "John Doe",
                "email": "john@example.com"
            }
        }
    ],
    "meta": {
        "total": 1,
        "per_page": 15,
        "current_page": 1
    }
}
```

### Update Task Reminder

Update an existing reminder (reschedule or change channel).

**Endpoint**: `PUT /api/v1/reminders/{reminder}`

**Parameters:**
- `reminder` (path) - Reminder ID

**Request Body:**
```json
{
    "remind_at": "2025-12-11T11:00:00Z",
    "channel": "database"
}
```

**Response:**
```json
{
    "data": {
        "id": 456,
        "task_id": 789,
        "user_id": 123,
        "remind_at": "2025-12-11T11:00:00Z",
        "channel": "database",
        "status": "pending",
        "updated_at": "2025-12-10T16:00:00Z"
    }
}
```

### Cancel Task Reminder

Cancel a specific reminder.

**Endpoint**: `DELETE /api/v1/reminders/{reminder}`

**Parameters:**
- `reminder` (path) - Reminder ID

**Response:**
```json
{
    "message": "Reminder canceled successfully"
}
```

### Cancel All Task Reminders

Cancel all pending reminders for a task.

**Endpoint**: `DELETE /api/v1/tasks/{task}/reminders`

**Parameters:**
- `task` (path) - Task ID

**Response:**
```json
{
    "message": "3 reminders canceled successfully"
}
```

## Error Responses

### Validation Error (422)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "remind_at": [
            "The remind at field is required."
        ],
        "channel": [
            "The selected channel is invalid."
        ]
    }
}
```

### Not Found (404)

```json
{
    "message": "Task not found"
}
```

### Unauthorized (401)

```json
{
    "message": "Unauthenticated."
}
```

### Forbidden (403)

```json
{
    "message": "This action is unauthorized."
}
```

## Rate Limiting

API endpoints are rate limited to prevent abuse:

- **Authenticated requests**: 60 requests per minute
- **Guest requests**: 10 requests per minute

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1702224000
```

## Authentication

### Bearer Token

Include the token in the Authorization header:

```
Authorization: Bearer your-api-token-here
```

### Sanctum Token

Generate tokens via the user dashboard or API:

```php
$token = $user->createToken('API Token')->plainTextToken;
```

## Pagination

List endpoints support pagination with the following parameters:

- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)

Response includes pagination metadata:

```json
{
    "data": [...],
    "links": {
        "first": "http://example.com/api/v1/tasks?page=1",
        "last": "http://example.com/api/v1/tasks?page=10",
        "prev": null,
        "next": "http://example.com/api/v1/tasks?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 10,
        "per_page": 15,
        "to": 15,
        "total": 150
    }
}
```

## Filtering and Sorting

### Filtering

Use query parameters to filter results:

```
GET /api/v1/tasks?status=pending&assignee=123
```

### Sorting

Use the `sort` parameter:

```
GET /api/v1/tasks?sort=created_at
GET /api/v1/tasks?sort=-created_at  # Descending
```

## Field Selection

Use the `fields` parameter to select specific fields:

```
GET /api/v1/tasks?fields=id,title,status
```

## Including Relationships

Use the `include` parameter to include related data:

```
GET /api/v1/tasks?include=assignees,reminders
```

## Webhook Events

The system can send webhooks for various events:

### Task Reminder Events

- `task.reminder.scheduled` - When a reminder is scheduled
- `task.reminder.sent` - When a reminder is sent
- `task.reminder.canceled` - When a reminder is canceled
- `task.reminder.failed` - When a reminder fails to send

### Webhook Payload

```json
{
    "event": "task.reminder.sent",
    "data": {
        "reminder": {
            "id": 456,
            "task_id": 789,
            "user_id": 123,
            "remind_at": "2025-12-11T10:00:00Z",
            "sent_at": "2025-12-11T10:00:05Z",
            "channel": "email",
            "status": "sent"
        },
        "task": {
            "id": 789,
            "title": "Complete project proposal",
            "status": "in_progress"
        }
    },
    "timestamp": "2025-12-11T10:00:05Z"
}
```

## SDK Examples

### PHP (Laravel HTTP Client)

```php
use Illuminate\Support\Facades\Http;

// Schedule a reminder
$response = Http::withToken($token)
    ->post('/api/v1/tasks/789/reminders', [
        'remind_at' => '2025-12-11T10:00:00Z',
        'channel' => 'email',
        'user_id' => 123,
    ]);

$reminder = $response->json('data');
```

### JavaScript (Fetch API)

```javascript
// Schedule a reminder
const response = await fetch('/api/v1/tasks/789/reminders', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        remind_at: '2025-12-11T10:00:00Z',
        channel: 'email',
        user_id: 123,
    }),
});

const { data: reminder } = await response.json();
```

### cURL

```bash
# Schedule a reminder
curl -X POST \
  -H "Authorization: Bearer your-token" \
  -H "Content-Type: application/json" \
  -d '{
    "remind_at": "2025-12-11T10:00:00Z",
    "channel": "email",
    "user_id": 123
  }' \
  https://your-domain.com/api/v1/tasks/789/reminders
```

## OpenAPI Specification

The complete OpenAPI specification is available at `/docs/api` when logged into the application.

## Testing Commands

### Test Coverage Agent

Enhanced test execution with intelligent coverage driver detection:

```bash
# Run comprehensive test suite with coverage analysis
php test-coverage-agent.php
```

**Features:**
- Automatic PCOV/Xdebug detection
- Progressive test execution (Basic → Unit → Feature → Coverage)
- Performance timing for each phase
- Graceful fallback when no coverage driver available

**Output Example:**
```
=== Test Coverage Agent ===
PCOV: ✅ Available
Xdebug: ❌ Not installed

✅ Basic tests passed! (2s)
✅ Unit tests passed! (15s)
✅ Feature tests passed! (12s)
✅ Coverage analysis complete! (8s)
```

### Related Testing Commands

```bash
# Profile test performance
composer test:pest:profile

# Run specific test suites
composer test:unit
composer test:feature
composer test:coverage

# Run with specific coverage driver
COVERAGE_DRIVER=pcov composer test:coverage
```

## Changelog

### Version 1.0.0 (December 10, 2025)

**Added:**
- Task Reminders API endpoints
- Comprehensive error handling
- Rate limiting documentation
- Authentication examples
- Webhook event specifications

**Features:**
- Schedule, update, and cancel reminders
- Multiple notification channels
- Pagination and filtering support
- Field selection and relationship inclusion