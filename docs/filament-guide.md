# Filament v4.3+ Integration Guide

## Overview

This guide covers Filament v4.3+ integration patterns, custom components, and best practices for the Relaticle CRM system.

**Version**: 4.3+  
**Last Updated**: December 10, 2025

## Task Reminder Integration

### Resource Actions

Add reminder management actions to the TaskResource:

```php
use App\Services\Task\TaskReminderService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

// In TaskResource.php
protected function getHeaderActions(): array
{
    return [
        Action::make('scheduleReminder')
            ->label(__('app.actions.schedule_reminder'))
            ->icon('heroicon-o-bell')
            ->color('warning')
            ->form([
                DateTimePicker::make('remind_at')
                    ->label(__('app.labels.remind_at'))
                    ->required()
                    ->minDate(now())
                    ->helperText(__('app.helpers.reminder_time')),
                    
                Select::make('channel')
                    ->label(__('app.labels.notification_channel'))
                    ->options(fn (TaskReminderService $service) => 
                        collect($service->getValidChannels())
                            ->mapWithKeys(fn ($channel) => [
                                $channel => __("app.channels.{$channel}")
                            ])
                    )
                    ->default('database')
                    ->required(),
                    
                Select::make('user_id')
                    ->label(__('app.labels.remind_user'))
                    ->relationship('assignees', 'name')
                    ->default(fn () => auth()->id())
                    ->required()
                    ->searchable()
                    ->preload(),
            ])
            ->action(function (Task $record, array $data, TaskReminderService $service) {
                $user = User::find($data['user_id']);
                
                $reminder = $service->scheduleReminder(
                    task: $record,
                    remindAt: Carbon::parse($data['remind_at']),
                    user: $user,
                    channel: $data['channel']
                );
                
                Notification::make()
                    ->title(__('app.notifications.reminder_scheduled'))
                    ->body(__('app.notifications.reminder_scheduled_body', [
                        'time' => $reminder->remind_at->format('M j, Y g:i A'),
                        'channel' => $data['channel'],
                    ]))
                    ->success()
                    ->send();
            })
            ->visible(fn (Task $record) => !$record->isCompleted()),
    ];
}
```

### Table Actions

Add reminder actions to the task table:

```php
use Filament\Tables\Actions\ActionGroup;

// In TaskResource table configuration
->actions([
    ActionGroup::make([
        EditAction::make(),
        
        Action::make('quickReminder')
            ->label(__('app.actions.quick_reminder'))
            ->icon('heroicon-o-bell')
            ->color('warning')
            ->form([
                Select::make('time_option')
                    ->label(__('app.labels.remind_in'))
                    ->options([
                        '15_minutes' => __('app.time_options.15_minutes'),
                        '1_hour' => __('app.time_options.1_hour'),
                        '1_day' => __('app.time_options.1_day'),
                        'custom' => __('app.time_options.custom'),
                    ])
                    ->default('1_hour')
                    ->live(),
                    
                DateTimePicker::make('custom_time')
                    ->label(__('app.labels.custom_time'))
                    ->visible(fn (Get $get) => $get('time_option') === 'custom')
                    ->required(fn (Get $get) => $get('time_option') === 'custom')
                    ->minDate(now()),
            ])
            ->action(function (Task $record, array $data, TaskReminderService $service) {
                $remindAt = match ($data['time_option']) {
                    '15_minutes' => now()->addMinutes(15),
                    '1_hour' => now()->addHour(),
                    '1_day' => now()->addDay(),
                    'custom' => Carbon::parse($data['custom_time']),
                };
                
                $service->scheduleReminder(
                    task: $record,
                    remindAt: $remindAt,
                    user: auth()->user()
                );
                
                Notification::make()
                    ->title(__('app.notifications.reminder_set'))
                    ->success()
                    ->send();
            })
            ->visible(fn (Task $record) => !$record->isCompleted()),
            
        DeleteAction::make(),
    ])
    ->label(__('app.actions.actions'))
    ->icon('heroicon-o-ellipsis-vertical')
    ->button(),
])
```

### Relation Manager

Create a dedicated relation manager for task reminders:

```php
// app/Filament/Resources/TaskResource/RelationManagers/RemindersRelationManager.php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use App\Models\TaskReminder;
use App\Services\Task\TaskReminderService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RemindersRelationManager extends RelationManager
{
    protected static string $relationship = 'reminders';
    
    protected static ?string $recordTitleAttribute = 'remind_at';
    
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('remind_at')
                    ->label(__('app.labels.remind_at'))
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
                    
                TextColumn::make('user.name')
                    ->label(__('app.labels.user'))
                    ->searchable(),
                    
                TextColumn::make('channel')
                    ->label(__('app.labels.channel'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'database' => 'gray',
                        'email' => 'blue',
                        'sms' => 'green',
                        'slack' => 'purple',
                        default => 'gray',
                    }),
                    
                TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'sent' => 'success',
                        'canceled' => 'gray',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                    
                TextColumn::make('sent_at')
                    ->label(__('app.labels.sent_at'))
                    ->dateTime('M j, Y g:i A')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->form([
                        DateTimePicker::make('remind_at')
                            ->label(__('app.labels.remind_at'))
                            ->required()
                            ->minDate(now()),
                            
                        Select::make('user_id')
                            ->label(__('app.labels.user'))
                            ->relationship('user', 'name')
                            ->default(fn () => auth()->id())
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        Select::make('channel')
                            ->label(__('app.labels.channel'))
                            ->options(fn (TaskReminderService $service) => 
                                collect($service->getValidChannels())
                                    ->mapWithKeys(fn ($channel) => [
                                        $channel => __("app.channels.{$channel}")
                                    ])
                            )
                            ->default('database')
                            ->required(),
                    ])
                    ->using(function (array $data, TaskReminderService $service) {
                        return $service->scheduleReminder(
                            task: $this->getOwnerRecord(),
                            remindAt: Carbon::parse($data['remind_at']),
                            user: User::find($data['user_id']),
                            channel: $data['channel']
                        );
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        DateTimePicker::make('remind_at')
                            ->label(__('app.labels.remind_at'))
                            ->required()
                            ->minDate(now()),
                            
                        Select::make('channel')
                            ->label(__('app.labels.channel'))
                            ->options(fn (TaskReminderService $service) => 
                                collect($service->getValidChannels())
                                    ->mapWithKeys(fn ($channel) => [
                                        $channel => __("app.channels.{$channel}")
                                    ])
                            )
                            ->required(),
                    ])
                    ->using(function (TaskReminder $record, array $data, TaskReminderService $service) {
                        if ($data['remind_at'] !== $record->remind_at->toISOString()) {
                            $service->rescheduleReminder(
                                $record,
                                Carbon::parse($data['remind_at'])
                            );
                        }
                        
                        if ($data['channel'] !== $record->channel) {
                            $record->update(['channel' => $data['channel']]);
                        }
                        
                        return $record;
                    })
                    ->visible(fn (TaskReminder $record) => 
                        $record->status === 'pending'
                    ),
                    
                Action::make('cancel')
                    ->label(__('app.actions.cancel'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(fn (TaskReminder $record, TaskReminderService $service) => 
                        $service->cancelReminder($record)
                    )
                    ->requiresConfirmation()
                    ->visible(fn (TaskReminder $record) => 
                        $record->status === 'pending'
                    ),
                    
                DeleteAction::make()
                    ->visible(fn (TaskReminder $record) => 
                        in_array($record->status, ['sent', 'canceled', 'failed'])
                    ),
            ])
            ->defaultSort('remind_at', 'desc')
            ->emptyStateHeading(__('app.empty_states.no_reminders'))
            ->emptyStateDescription(__('app.empty_states.no_reminders_description'))
            ->emptyStateIcon('heroicon-o-bell-slash');
    }
}
```

### Widget Integration

Create a dashboard widget for upcoming reminders:

```php
// app/Filament/Widgets/UpcomingRemindersWidget.php

namespace App\Filament\Widgets;

use App\Models\TaskReminder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingRemindersWidget extends BaseWidget
{
    protected static ?string $heading = null;
    
    protected int | string | array $columnSpan = 'full';
    
    public static function getHeading(): string
    {
        return __('app.widgets.upcoming_reminders');
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                TaskReminder::query()
                    ->where('user_id', auth()->id())
                    ->where('status', 'pending')
                    ->whereNull('sent_at')
                    ->whereNull('canceled_at')
                    ->where('remind_at', '>=', now())
                    ->where('remind_at', '<=', now()->addDays(7))
                    ->with(['task', 'user'])
                    ->orderBy('remind_at')
            )
            ->columns([
                TextColumn::make('task.title')
                    ->label(__('app.labels.task'))
                    ->limit(50)
                    ->url(fn (TaskReminder $record) => 
                        TaskResource::getUrl('view', ['record' => $record->task])
                    ),
                    
                TextColumn::make('remind_at')
                    ->label(__('app.labels.remind_at'))
                    ->since()
                    ->description(fn (TaskReminder $record) => 
                        $record->remind_at->format('M j, Y g:i A')
                    ),
                    
                TextColumn::make('channel')
                    ->label(__('app.labels.channel'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'database' => 'gray',
                        'email' => 'blue',
                        'sms' => 'green',
                        'slack' => 'purple',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(fn (TaskReminder $record, TaskReminderService $service) => 
                        $service->cancelReminder($record)
                    )
                    ->requiresConfirmation(),
            ])
            ->emptyStateHeading(__('app.empty_states.no_upcoming_reminders'))
            ->emptyStateDescription(__('app.empty_states.no_upcoming_reminders_description'))
            ->emptyStateIcon('heroicon-o-bell-slash')
            ->paginated(false);
    }
}
```

### Custom Form Components

Create reusable reminder form components:

```php
// app/Filament/Forms/Components/ReminderScheduler.php

namespace App\Filament\Forms\Components;

use App\Services\Task\TaskReminderService;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;

class ReminderScheduler extends Component
{
    protected string $view = 'filament.forms.components.reminder-scheduler';
    
    public static function make(string $name = 'reminder'): static
    {
        return app(static::class, ['name' => $name]);
    }
    
    public function getChildComponents(): array
    {
        return [
            Section::make(__('app.sections.schedule_reminder'))
                ->schema([
                    Select::make('quick_options')
                        ->label(__('app.labels.quick_options'))
                        ->options([
                            '15_minutes' => __('app.time_options.15_minutes'),
                            '1_hour' => __('app.time_options.1_hour'),
                            '4_hours' => __('app.time_options.4_hours'),
                            '1_day' => __('app.time_options.1_day'),
                            '1_week' => __('app.time_options.1_week'),
                            'custom' => __('app.time_options.custom'),
                        ])
                        ->placeholder(__('app.placeholders.select_time'))
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state !== 'custom') {
                                $remindAt = match ($state) {
                                    '15_minutes' => now()->addMinutes(15),
                                    '1_hour' => now()->addHour(),
                                    '4_hours' => now()->addHours(4),
                                    '1_day' => now()->addDay(),
                                    '1_week' => now()->addWeek(),
                                    default => null,
                                };
                                
                                if ($remindAt) {
                                    $set('remind_at', $remindAt);
                                }
                            }
                        }),
                        
                    DateTimePicker::make('remind_at')
                        ->label(__('app.labels.remind_at'))
                        ->required()
                        ->minDate(now())
                        ->visible(fn (Get $get) => 
                            $get('quick_options') === 'custom' || 
                            filled($get('remind_at'))
                        ),
                        
                    Select::make('channel')
                        ->label(__('app.labels.notification_channel'))
                        ->options(fn (TaskReminderService $service) => 
                            collect($service->getValidChannels())
                                ->mapWithKeys(fn ($channel) => [
                                    $channel => __("app.channels.{$channel}")
                                ])
                        )
                        ->default('database')
                        ->required(),
                        
                    Select::make('user_id')
                        ->label(__('app.labels.remind_user'))
                        ->relationship('assignees', 'name')
                        ->default(fn () => auth()->id())
                        ->required()
                        ->searchable()
                        ->preload(),
                ])
                ->collapsible()
                ->collapsed(false),
        ];
    }
}
```

### Notification Integration

Integrate with Filament's notification system:

```php
// In TaskObserver or relevant event listener
use Filament\Notifications\Notification;

public function updated(Task $task): void
{
    // Cancel reminders when task is completed
    if ($task->isCompleted() && $task->hasPendingReminders()) {
        $canceledCount = $task->cancelReminders();
        
        Notification::make()
            ->title(__('app.notifications.reminders_canceled'))
            ->body(__('app.notifications.reminders_canceled_body', [
                'count' => $canceledCount
            ]))
            ->info()
            ->sendToDatabase($task->assignees);
    }
}
```

### Translation Keys

Add the following translation keys to support the reminder system:

```php
// lang/en/app.php
return [
    'actions' => [
        'schedule_reminder' => 'Schedule Reminder',
        'quick_reminder' => 'Quick Reminder',
        'cancel' => 'Cancel',
    ],
    
    'labels' => [
        'remind_at' => 'Remind At',
        'remind_user' => 'Remind User',
        'notification_channel' => 'Notification Channel',
        'channel' => 'Channel',
        'status' => 'Status',
        'sent_at' => 'Sent At',
        'quick_options' => 'Quick Options',
        'custom_time' => 'Custom Time',
    ],
    
    'channels' => [
        'database' => 'In-App Notification',
        'email' => 'Email',
        'sms' => 'SMS',
        'slack' => 'Slack',
    ],
    
    'time_options' => [
        '15_minutes' => 'In 15 minutes',
        '1_hour' => 'In 1 hour',
        '4_hours' => 'In 4 hours',
        '1_day' => 'In 1 day',
        '1_week' => 'In 1 week',
        'custom' => 'Custom time',
    ],
    
    'notifications' => [
        'reminder_scheduled' => 'Reminder Scheduled',
        'reminder_scheduled_body' => 'Reminder set for :time via :channel',
        'reminder_set' => 'Reminder Set',
        'reminders_canceled' => 'Reminders Canceled',
        'reminders_canceled_body' => ':count reminder(s) canceled for completed task',
        'task_reminder' => 'Task Reminder',
    ],
    
    'widgets' => [
        'upcoming_reminders' => 'Upcoming Reminders',
    ],
    
    'sections' => [
        'schedule_reminder' => 'Schedule Reminder',
    ],
    
    'empty_states' => [
        'no_reminders' => 'No Reminders',
        'no_reminders_description' => 'This task has no scheduled reminders.',
        'no_upcoming_reminders' => 'No Upcoming Reminders',
        'no_upcoming_reminders_description' => 'You have no reminders in the next 7 days.',
    ],
    
    'helpers' => [
        'reminder_time' => 'When should we remind you about this task?',
    ],
    
    'placeholders' => [
        'select_time' => 'Select a time option',
    ],
];
```

## Best Practices

### Performance Optimization

1. **Eager Loading**: Always eager load relationships in table queries
2. **Caching**: Cache frequently accessed data like user lists
3. **Pagination**: Use appropriate pagination limits
4. **Indexes**: Ensure database indexes support your queries

### User Experience

1. **Validation**: Provide clear validation messages
2. **Feedback**: Show success/error notifications
3. **Accessibility**: Use proper labels and descriptions
4. **Responsive**: Ensure components work on all screen sizes

### Code Organization

1. **Separation of Concerns**: Keep business logic in services
2. **Reusability**: Create reusable components and actions
3. **Consistency**: Follow naming conventions
4. **Documentation**: Document complex components

### Security

1. **Authorization**: Check permissions for all actions
2. **Validation**: Validate all user inputs
3. **Sanitization**: Sanitize data before display
4. **Rate Limiting**: Implement appropriate rate limits

## Testing Filament Components

### Component Tests

```php
use function Pest\Livewire\livewire;

it('can schedule a reminder', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create();
    
    $this->actingAs($user);
    
    livewire(EditTask::class, ['record' => $task->getRouteKey()])
        ->callAction('scheduleReminder', [
            'remind_at' => now()->addHour()->toISOString(),
            'channel' => 'email',
            'user_id' => $user->id,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();
    
    expect($task->reminders)->toHaveCount(1);
});
```

### Widget Tests

```php
it('displays upcoming reminders widget', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create();
    
    TaskReminder::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'remind_at' => now()->addHour(),
        'status' => 'pending',
    ]);
    
    $this->actingAs($user);
    
    livewire(UpcomingRemindersWidget::class)
        ->assertCanSeeTableRecords([$reminder]);
});
```

## Related Documentation

- [Task Reminder System](task-reminder-system.md)
- [Filament v4.3+ Conventions](.kiro/steering/filament-conventions.md)
- [Translation System](.kiro/steering/translations.md)
- [Testing Standards](.kiro/steering/testing-standards.md)

## Changelog

### Version 1.0.0 (December 10, 2025)

**Added:**
- Task reminder resource actions
- Reminder relation manager
- Upcoming reminders widget
- Custom form components
- Comprehensive translation keys

**Features:**
- Schedule reminders with multiple channels
- Quick reminder options
- Real-time status updates
- Bulk reminder management
- Integration with notification system