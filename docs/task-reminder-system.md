# Task Reminder System

## Overview

The Task Reminder System provides automated notifications for tasks at scheduled times. It supports multiple notification channels (database, email, SMS, Slack) and integrates seamlessly with Laravel's queue system for reliable delivery.

**Version**: 1.0.0  
**Last Updated**: December 10, 2025

## Architecture

### Components

1. **TaskReminderService** - Core service for managing reminders
2. **TaskReminder Model** - Database representation of reminders
3. **SendTaskReminderJob** - Queue job for sending notifications
4. **ProcessTaskRemindersCommand** - Scheduled command to process due reminders

### Service Pattern

The `TaskReminderService` follows the Laravel Container Service pattern:

- **Singleton Registration**: Registered in `AppServiceProvider`
- **Constructor Injection**: Dependencies injected via readonly properties
- **Stateless Operations**: All methods are stateless and focused
- **Type Safety**: Full type hints and return types

## TaskReminderService API

### Class Definition

```php
namespace App\Services\Task;

final class TaskReminderService
{
    private const VALID_CHANNELS = ['database', 'email', 'sms', 'slack'];
}
```

### Methods

#### scheduleReminder()

Schedule a reminder for a task at a specific time.

**Signature:**
```php
public function scheduleReminder(
    Task $task,
    Carbon $remindAt,
    User $user,
    string $channel = 'database'
): TaskReminder
```

**Parameters:**
- `$task` - The task to set a reminder for
- `$remindAt` - When to send the reminder
- `$user` - The user to remind
- `$channel` - Notification channel (database, email, sms, slack)

**Returns:** The created `TaskReminder` instance

**Throws:** `InvalidArgumentException` if channel is invalid

**Example:**
```php
$service = app(TaskReminderService::class);

// Schedule a reminder for 1 hour before task due date
$reminder = $service->scheduleReminder(
    task: $task,
    remindAt: $task->dueDate()->subHour(),
    user: auth()->user(),
    channel: 'email'
);
```

#### sendDueReminders()

Process and send all reminders that are due.

**Signature:**
```php
public function sendDueReminders(): Collection
```

**Returns:** Collection of `TaskReminder` instances that were processed

**Example:**
```php
// Typically called via scheduled command
$sentReminders = $service->sendDueReminders();

foreach ($sentReminders as $reminder) {
    Log::info("Sent reminder {$reminder->id} for task {$reminder->task_id}");
}
```

#### cancelTaskReminders()

Cancel all pending reminders for a task.

**Signature:**
```php
public function cancelTaskReminders(Task $task): int
```

**Parameters:**
- `$task` - The task whose reminders should be canceled

**Returns:** Number of reminders canceled

**Example:**
```php
// Cancel reminders when task is completed
if ($task->isCompleted()) {
    $canceledCount = $service->cancelTaskReminders($task);
}
```

#### getPendingReminders()

Get all pending reminders for a task.

**Signature:**
```php
public function getPendingReminders(Task $task): Collection
```

**Parameters:**
- `$task` - The task to get reminders for

**Returns:** Collection of pending `TaskReminder` instances

**Example:**
```php
$pendingReminders = $service->getPendingReminders($task);

foreach ($pendingReminders as $reminder) {
    echo "Reminder scheduled for {$reminder->remind_at->format('Y-m-d H:i')}";
}
```

#### getTaskReminders()

Get all reminders for a task (including sent and canceled).

**Signature:**
```php
public function getTaskReminders(Task $task): Collection
```

**Parameters:**
- `$task` - The task to get reminders for

**Returns:** Collection of all `TaskReminder` instances

**Example:**
```php
$allReminders = $service->getTaskReminders($task);
$history = $allReminders->map(fn($r) => [
    'time' => $r->remind_at,
    'status' => $r->status,
    'channel' => $r->channel,
]);
```

#### cancelReminder()

Cancel a specific reminder.

**Signature:**
```php
public function cancelReminder(TaskReminder $reminder): bool
```

**Parameters:**
- `$reminder` - The reminder to cancel

**Returns:** `true` if canceled successfully, `false` if already sent/canceled

**Example:**
```php
if ($service->cancelReminder($reminder)) {
    Notification::make()
        ->title('Reminder canceled')
        ->success()
        ->send();
}
```

#### rescheduleReminder()

Reschedule a reminder to a new time.

**Signature:**
```php
public function rescheduleReminder(TaskReminder $reminder, Carbon $newRemindAt): bool
```

**Parameters:**
- `$reminder` - The reminder to reschedule
- `$newRemindAt` - The new reminder time

**Returns:** `true` if rescheduled successfully, `false` if already sent/canceled

**Example:**
```php
// Reschedule to 30 minutes later
$newTime = $reminder->remind_at->addMinutes(30);

if ($service->rescheduleReminder($reminder, $newTime)) {
    Notification::make()
        ->title('Reminder rescheduled')
        ->success()
        ->send();
}
```

#### getValidChannels()

Get list of valid notification channels.

**Signature:**
```php
public function getValidChannels(): array
```

**Returns:** Array of valid channel names

**Example:**
```php
$channels = $service->getValidChannels();
// ['database', 'email', 'sms', 'slack']
```

#### isValidChannel()

Check if a channel is valid.

**Signature:**
```php
public function isValidChannel(string $channel): bool
```

**Parameters:**
- `$channel` - The channel to validate

**Returns:** `true` if valid, `false` otherwise

**Example:**
```php
if ($service->isValidChannel($userChannel)) {
    $service->scheduleReminder($task, $time, $user, $userChannel);
}
```

## Task Model Integration

The `Task` model includes convenience methods for reminder management:

### scheduleReminder()

```php
$task->scheduleReminder(
    remindAt: now()->addDay(),
    user: auth()->user(),
    channel: 'email'
);
```

### cancelReminders()

```php
$canceledCount = $task->cancelReminders();
```

### getPendingReminders()

```php
$pending = $task->getPendingReminders();
```

### hasPendingReminders()

```php
if ($task->hasPendingReminders()) {
    // Show reminder indicator
}
```

## Database Schema

### task_reminders Table

```sql
CREATE TABLE task_reminders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    task_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    remind_at TIMESTAMP NOT NULL,
    sent_at TIMESTAMP NULL,
    canceled_at TIMESTAMP NULL,
    channel VARCHAR(255) NOT NULL DEFAULT 'database',
    status VARCHAR(255) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_remind_at (remind_at),
    INDEX idx_status (status),
    INDEX idx_task_user (task_id, user_id)
);
```

### Status Values

- `pending` - Reminder scheduled but not sent
- `sent` - Reminder successfully sent
- `canceled` - Reminder canceled before sending
- `failed` - Reminder failed to send

## Automation

### Scheduled Command

The `ProcessTaskRemindersCommand` runs automatically to process due reminders:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('tasks:process-reminders')
        ->everyFiveMinutes()
        ->withoutOverlapping();
}
```

### Queue Job

The `SendTaskReminderJob` handles actual notification sending:

```php
// Dispatched automatically by ProcessTaskRemindersCommand
dispatch(new SendTaskReminderJob($reminder));
```

**Features:**
- Implements `ShouldBeUnique` to prevent duplicate sends
- Checks reminder status before sending
- Supports multiple notification channels
- Updates reminder status after sending

## Notification Channels

### Database (Default)

Sends Filament notification to user's notification panel.

```php
$service->scheduleReminder($task, $time, $user, 'database');
```

### Email

Sends email notification (requires mail configuration).

```php
$service->scheduleReminder($task, $time, $user, 'email');
```

### SMS

Sends SMS notification (requires SMS provider configuration).

```php
$service->scheduleReminder($task, $time, $user, 'sms');
```

### Slack

Sends Slack notification (requires Slack webhook configuration).

```php
$service->scheduleReminder($task, $time, $user, 'slack');
```

## Filament Integration

### Resource Actions

Add reminder actions to TaskResource:

```php
use App\Services\Task\TaskReminderService;
use Filament\Actions\Action;

Action::make('scheduleReminder')
    ->label(__('app.actions.schedule_reminder'))
    ->icon('heroicon-o-bell')
    ->form([
        DateTimePicker::make('remind_at')
            ->label(__('app.labels.remind_at'))
            ->required()
            ->minDate(now()),
        Select::make('channel')
            ->label(__('app.labels.channel'))
            ->options([
                'database' => __('app.channels.database'),
                'email' => __('app.channels.email'),
                'sms' => __('app.channels.sms'),
                'slack' => __('app.channels.slack'),
            ])
            ->default('database')
            ->required(),
    ])
    ->action(function (Task $record, array $data, TaskReminderService $service) {
        $service->scheduleReminder(
            task: $record,
            remindAt: Carbon::parse($data['remind_at']),
            user: auth()->user(),
            channel: $data['channel']
        );
        
        Notification::make()
            ->title(__('app.notifications.reminder_scheduled'))
            ->success()
            ->send();
    });
```

### Relation Manager

Create a `RemindersRelationManager` for tasks:

```php
use Filament\Resources\RelationManagers\RelationManager;

class RemindersRelationManager extends RelationManager
{
    protected static string $relationship = 'reminders';
    
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('remind_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('channel')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'sent' => 'success',
                        'canceled' => 'gray',
                        'failed' => 'danger',
                    }),
                TextColumn::make('sent_at')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->actions([
                Action::make('cancel')
                    ->visible(fn (TaskReminder $record) => 
                        $record->status === 'pending'
                    )
                    ->action(fn (TaskReminder $record, TaskReminderService $service) => 
                        $service->cancelReminder($record)
                    ),
            ]);
    }
}
```

## Testing

### Unit Tests

```php
use App\Services\Task\TaskReminderService;
use App\Models\Task;
use App\Models\User;

it('schedules a reminder', function () {
    $service = app(TaskReminderService::class);
    $task = Task::factory()->create();
    $user = User::factory()->create();
    
    $reminder = $service->scheduleReminder(
        $task,
        now()->addHour(),
        $user,
        'email'
    );
    
    expect($reminder)->toBeInstanceOf(TaskReminder::class);
    expect($reminder->status)->toBe('pending');
    expect($reminder->channel)->toBe('email');
});

it('cancels task reminders', function () {
    $service = app(TaskReminderService::class);
    $task = Task::factory()->create();
    $user = User::factory()->create();
    
    // Create 3 reminders
    $service->scheduleReminder($task, now()->addHour(), $user);
    $service->scheduleReminder($task, now()->addDay(), $user);
    $service->scheduleReminder($task, now()->addWeek(), $user);
    
    $canceledCount = $service->cancelTaskReminders($task);
    
    expect($canceledCount)->toBe(3);
    expect($task->getPendingReminders())->toHaveCount(0);
});
```

### Feature Tests

```php
it('sends due reminders', function () {
    Queue::fake();
    
    $service = app(TaskReminderService::class);
    $task = Task::factory()->create();
    $user = User::factory()->create();
    
    // Create a reminder that's due
    $reminder = $service->scheduleReminder(
        $task,
        now()->subMinute(),
        $user
    );
    
    $sentReminders = $service->sendDueReminders();
    
    expect($sentReminders)->toHaveCount(1);
    expect($reminder->fresh()->status)->toBe('sent');
});
```

## Best Practices

### DO:
- ✅ Use the service layer for all reminder operations
- ✅ Validate channels before scheduling
- ✅ Cancel reminders when tasks are completed
- ✅ Use transactions for bulk operations
- ✅ Log reminder operations for debugging
- ✅ Test reminder workflows thoroughly
- ✅ Handle timezone differences appropriately

### DON'T:
- ❌ Create reminders directly without the service
- ❌ Skip validation of reminder times
- ❌ Forget to cancel reminders for deleted tasks
- ❌ Send reminders synchronously (use queues)
- ❌ Ignore failed reminder notifications
- ❌ Create duplicate reminders for the same time

## Performance Considerations

### Database Indexes

Ensure proper indexes exist for efficient queries:

```sql
CREATE INDEX idx_remind_at ON task_reminders(remind_at);
CREATE INDEX idx_status ON task_reminders(status);
CREATE INDEX idx_task_user ON task_reminders(task_id, user_id);
```

### Query Optimization

The service uses eager loading to prevent N+1 queries:

```php
$reminders = TaskReminder::query()
    ->where('status', 'pending')
    ->with(['task', 'user']) // Eager load relationships
    ->get();
```

### Queue Configuration

Configure appropriate queue settings for reminder processing:

```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

## Troubleshooting

### Reminders Not Sending

1. Check scheduled command is running:
   ```bash
   php artisan schedule:list
   ```

2. Verify queue worker is active:
   ```bash
   php artisan queue:work
   ```

3. Check reminder status in database:
   ```sql
   SELECT * FROM task_reminders WHERE status = 'pending';
   ```

### Duplicate Reminders

The job implements `ShouldBeUnique` to prevent duplicates. If duplicates occur:

1. Check Redis connection
2. Verify unique ID generation
3. Review queue configuration

### Failed Notifications

Check logs for notification failures:

```bash
tail -f storage/logs/laravel.log | grep "task-reminder"
```

## Related Documentation

- [Laravel Container Services](laravel-container-services.md)
- [Task Management System](task-management-system.md)
- [Queue Configuration](queue-configuration.md)
- [Notification System](notification-system.md)

## Changelog

### Version 1.0.0 (December 10, 2025)

**Added:**
- Initial TaskReminderService implementation
- Support for multiple notification channels
- Task model integration methods
- Scheduled command for processing reminders
- Queue job for sending notifications
- Comprehensive PHPDoc documentation

**Features:**
- Schedule reminders with custom channels
- Cancel individual or all task reminders
- Reschedule existing reminders
- Get pending/all reminders for tasks
- Validate notification channels
- Atomic operations with database transactions

**Testing:**
- Unit tests for service methods
- Feature tests for reminder workflows
- Integration tests with queue system
