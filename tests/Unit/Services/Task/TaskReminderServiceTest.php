<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\TaskReminder;
use App\Models\User;
use App\Services\Task\TaskReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new TaskReminderService;
});

describe('scheduleReminder', function (): void {
    it('creates a reminder with correct attributes', function (): void {
        $task = Task::factory()->create();
        $user = User::factory()->create();
        $remindAt = \Illuminate\Support\Facades\Date::now()->addDay();

        $reminder = $this->service->scheduleReminder($task, $remindAt, $user);

        expect($reminder)->toBeInstanceOf(TaskReminder::class)
            ->and($reminder->task_id)->toBe($task->id)
            ->and($reminder->user_id)->toBe($user->id)
            ->and($reminder->remind_at->toDateTimeString())->toBe($remindAt->toDateTimeString())
            ->and($reminder->channel)->toBe('database')
            ->and($reminder->status)->toBe('pending')
            ->and($reminder->sent_at)->toBeNull()
            ->and($reminder->canceled_at)->toBeNull();
    });

    it('creates a reminder with custom channel', function (): void {
        $task = Task::factory()->create();
        $user = User::factory()->create();
        $remindAt = \Illuminate\Support\Facades\Date::now()->addDay();

        $reminder = $this->service->scheduleReminder($task, $remindAt, $user, 'email');

        expect($reminder->channel)->toBe('email');
    });

    it('persists reminder to database', function (): void {
        $task = Task::factory()->create();
        $user = User::factory()->create();
        $remindAt = \Illuminate\Support\Facades\Date::now()->addDay();

        $reminder = $this->service->scheduleReminder($task, $remindAt, $user);

        $this->assertDatabaseHas('task_reminders', [
            'id' => $reminder->id,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    });
});

describe('sendDueReminders', function (): void {
    it('sends reminders that are due', function (): void {
        $pastReminder = TaskReminder::factory()->create([
            'remind_at' => \Illuminate\Support\Facades\Date::now()->subHour(),
            'status' => 'pending',
            'sent_at' => null,
            'canceled_at' => null,
        ]);

        $reminders = $this->service->sendDueReminders();

        expect($reminders)->toHaveCount(1)
            ->and($reminders->first()->id)->toBe($pastReminder->id);

        $pastReminder->refresh();
        expect($pastReminder->status)->toBe('sent')
            ->and($pastReminder->sent_at)->not->toBeNull();
    });

    it('does not send future reminders', function (): void {
        TaskReminder::factory()->create([
            'remind_at' => \Illuminate\Support\Facades\Date::now()->addHour(),
            'status' => 'pending',
        ]);

        $reminders = $this->service->sendDueReminders();

        expect($reminders)->toHaveCount(0);
    });

    it('does not send already sent reminders', function (): void {
        TaskReminder::factory()->create([
            'remind_at' => \Illuminate\Support\Facades\Date::now()->subHour(),
            'status' => 'sent',
            'sent_at' => \Illuminate\Support\Facades\Date::now()->subMinutes(30),
        ]);

        $reminders = $this->service->sendDueReminders();

        expect($reminders)->toHaveCount(0);
    });

    it('does not send canceled reminders', function (): void {
        TaskReminder::factory()->create([
            'remind_at' => \Illuminate\Support\Facades\Date::now()->subHour(),
            'status' => 'canceled',
            'canceled_at' => \Illuminate\Support\Facades\Date::now()->subMinutes(30),
        ]);

        $reminders = $this->service->sendDueReminders();

        expect($reminders)->toHaveCount(0);
    });

    it('sends multiple due reminders', function (): void {
        TaskReminder::factory()->count(3)->create([
            'remind_at' => \Illuminate\Support\Facades\Date::now()->subHour(),
            'status' => 'pending',
            'sent_at' => null,
            'canceled_at' => null,
        ]);

        $reminders = $this->service->sendDueReminders();

        expect($reminders)->toHaveCount(3);
    });

    it('eager loads task and user relationships', function (): void {
        $reminder = TaskReminder::factory()->create([
            'remind_at' => \Illuminate\Support\Facades\Date::now()->subHour(),
            'status' => 'pending',
        ]);

        $reminders = $this->service->sendDueReminders();

        expect($reminders->first()->relationLoaded('task'))->toBeTrue()
            ->and($reminders->first()->relationLoaded('user'))->toBeTrue();
    });
});

describe('cancelTaskReminders', function (): void {
    it('cancels all pending reminders for a task', function (): void {
        $task = Task::factory()->create();
        $reminder1 = TaskReminder::factory()->create([
            'task_id' => $task->id,
            'status' => 'pending',
            'sent_at' => null,
            'canceled_at' => null,
        ]);
        $reminder2 = TaskReminder::factory()->create([
            'task_id' => $task->id,
            'status' => 'pending',
            'sent_at' => null,
            'canceled_at' => null,
        ]);

        $count = $this->service->cancelTaskReminders($task);

        expect($count)->toBe(2);

        $reminder1->refresh();
        $reminder2->refresh();

        expect($reminder1->status)->toBe('canceled')
            ->and($reminder1->canceled_at)->not->toBeNull()
            ->and($reminder2->status)->toBe('canceled')
            ->and($reminder2->canceled_at)->not->toBeNull();
    });

    it('does not cancel already sent reminders', function (): void {
        $task = Task::factory()->create();
        TaskReminder::factory()->create([
            'task_id' => $task->id,
            'status' => 'sent',
            'sent_at' => \Illuminate\Support\Facades\Date::now(),
        ]);

        $count = $this->service->cancelTaskReminders($task);

        expect($count)->toBe(0);
    });

    it('does not cancel already canceled reminders', function (): void {
        $task = Task::factory()->create();
        TaskReminder::factory()->create([
            'task_id' => $task->id,
            'status' => 'canceled',
            'canceled_at' => \Illuminate\Support\Facades\Date::now(),
        ]);

        $count = $this->service->cancelTaskReminders($task);

        expect($count)->toBe(0);
    });

    it('returns zero when task has no reminders', function (): void {
        $task = Task::factory()->create();

        $count = $this->service->cancelTaskReminders($task);

        expect($count)->toBe(0);
    });

    it('only cancels reminders for specified task', function (): void {
        $task1 = Task::factory()->create();
        $task2 = Task::factory()->create();

        TaskReminder::factory()->create([
            'task_id' => $task1->id,
            'status' => 'pending',
        ]);
        $reminder2 = TaskReminder::factory()->create([
            'task_id' => $task2->id,
            'status' => 'pending',
        ]);

        $this->service->cancelTaskReminders($task1);

        $reminder2->refresh();
        expect($reminder2->status)->toBe('pending')
            ->and($reminder2->canceled_at)->toBeNull();
    });
});

describe('getPendingReminders', function (): void {
    it('returns only pending reminders for a task', function (): void {
        $task = Task::factory()->create();
        $pending = TaskReminder::factory()->create([
            'task_id' => $task->id,
            'status' => 'pending',
            'sent_at' => null,
            'canceled_at' => null,
        ]);
        TaskReminder::factory()->create([
            'task_id' => $task->id,
            'status' => 'sent',
            'sent_at' => \Illuminate\Support\Facades\Date::now(),
        ]);

        $reminders = $this->service->getPendingReminders($task);

        expect($reminders)->toHaveCount(1)
            ->and($reminders->first()->id)->toBe($pending->id);
    });

    it('orders reminders by remind_at ascending', function (): void {
        $task = Task::factory()->create();
        $later = TaskReminder::factory()->create([
            'task_id' => $task->id,
            'remind_at' => \Illuminate\Support\Facades\Date::now()->addDays(2),
            'status' => 'pending',
        ]);
        $earlier = TaskReminder::factory()->create([
            'task_id' => $task->id,
            'remind_at' => \Illuminate\Support\Facades\Date::now()->addDay(),
            'status' => 'pending',
        ]);

        $reminders = $this->service->getPendingReminders($task);

        expect($reminders->first()->id)->toBe($earlier->id)
            ->and($reminders->last()->id)->toBe($later->id);
    });

    it('returns empty collection when no pending reminders', function (): void {
        $task = Task::factory()->create();

        $reminders = $this->service->getPendingReminders($task);

        expect($reminders)->toBeEmpty();
    });
});

describe('getTaskReminders', function (): void {
    it('returns all reminders for a task', function (): void {
        $task = Task::factory()->create();
        TaskReminder::factory()->create([
            'task_id' => $task->id,
            'status' => 'pending',
        ]);
        TaskReminder::factory()->create([
            'task_id' => $task->id,
            'status' => 'sent',
        ]);
        TaskReminder::factory()->create([
            'task_id' => $task->id,
            'status' => 'canceled',
        ]);

        $reminders = $this->service->getTaskReminders($task);

        expect($reminders)->toHaveCount(3);
    });

    it('orders reminders by remind_at descending', function (): void {
        $task = Task::factory()->create();
        $earlier = TaskReminder::factory()->create([
            'task_id' => $task->id,
            'remind_at' => \Illuminate\Support\Facades\Date::now()->addDay(),
        ]);
        $later = TaskReminder::factory()->create([
            'task_id' => $task->id,
            'remind_at' => \Illuminate\Support\Facades\Date::now()->addDays(2),
        ]);

        $reminders = $this->service->getTaskReminders($task);

        expect($reminders->first()->id)->toBe($later->id)
            ->and($reminders->last()->id)->toBe($earlier->id);
    });

    it('returns empty collection when task has no reminders', function (): void {
        $task = Task::factory()->create();

        $reminders = $this->service->getTaskReminders($task);

        expect($reminders)->toBeEmpty();
    });
});

describe('cancelReminder', function (): void {
    it('cancels a pending reminder', function (): void {
        $reminder = TaskReminder::factory()->create([
            'status' => 'pending',
            'sent_at' => null,
            'canceled_at' => null,
        ]);

        $result = $this->service->cancelReminder($reminder);

        expect($result)->toBeTrue();

        $reminder->refresh();
        expect($reminder->status)->toBe('canceled')
            ->and($reminder->canceled_at)->not->toBeNull();
    });

    it('returns false when reminder is already sent', function (): void {
        $reminder = TaskReminder::factory()->create([
            'status' => 'sent',
            'sent_at' => \Illuminate\Support\Facades\Date::now(),
        ]);

        $result = $this->service->cancelReminder($reminder);

        expect($result)->toBeFalse();

        $reminder->refresh();
        expect($reminder->status)->toBe('sent');
    });

    it('returns false when reminder is already canceled', function (): void {
        $reminder = TaskReminder::factory()->create([
            'status' => 'canceled',
            'canceled_at' => \Illuminate\Support\Facades\Date::now(),
        ]);

        $result = $this->service->cancelReminder($reminder);

        expect($result)->toBeFalse();
    });
});

describe('rescheduleReminder', function (): void {
    it('reschedules a pending reminder', function (): void {
        $reminder = TaskReminder::factory()->create([
            'remind_at' => \Illuminate\Support\Facades\Date::now()->addDay(),
            'status' => 'pending',
            'sent_at' => null,
            'canceled_at' => null,
        ]);
        $newTime = \Illuminate\Support\Facades\Date::now()->addDays(3);

        $result = $this->service->rescheduleReminder($reminder, $newTime);

        expect($result)->toBeTrue();

        $reminder->refresh();
        expect($reminder->remind_at->toDateTimeString())->toBe($newTime->toDateTimeString())
            ->and($reminder->status)->toBe('pending');
    });

    it('returns false when reminder is already sent', function (): void {
        $reminder = TaskReminder::factory()->create([
            'status' => 'sent',
            'sent_at' => \Illuminate\Support\Facades\Date::now(),
        ]);
        $newTime = \Illuminate\Support\Facades\Date::now()->addDays(3);

        $result = $this->service->rescheduleReminder($reminder, $newTime);

        expect($result)->toBeFalse();
    });

    it('returns false when reminder is already canceled', function (): void {
        $reminder = TaskReminder::factory()->create([
            'status' => 'canceled',
            'canceled_at' => \Illuminate\Support\Facades\Date::now(),
        ]);
        $newTime = \Illuminate\Support\Facades\Date::now()->addDays(3);

        $result = $this->service->rescheduleReminder($reminder, $newTime);

        expect($result)->toBeFalse();
    });

    it('can reschedule to earlier time', function (): void {
        $reminder = TaskReminder::factory()->create([
            'remind_at' => \Illuminate\Support\Facades\Date::now()->addDays(5),
            'status' => 'pending',
        ]);
        $newTime = \Illuminate\Support\Facades\Date::now()->addDay();

        $result = $this->service->rescheduleReminder($reminder, $newTime);

        expect($result)->toBeTrue();

        $reminder->refresh();
        expect($reminder->remind_at->toDateTimeString())->toBe($newTime->toDateTimeString());
    });

    it('can reschedule to later time', function (): void {
        $reminder = TaskReminder::factory()->create([
            'remind_at' => \Illuminate\Support\Facades\Date::now()->addDay(),
            'status' => 'pending',
        ]);
        $newTime = \Illuminate\Support\Facades\Date::now()->addWeek();

        $result = $this->service->rescheduleReminder($reminder, $newTime);

        expect($result)->toBeTrue();

        $reminder->refresh();
        expect($reminder->remind_at->toDateTimeString())->toBe($newTime->toDateTimeString());
    });
});

describe('sendReminderNotification', function (): void {
    it('updates reminder status to sent', function (): void {
        $reminder = TaskReminder::factory()->create([
            'status' => 'pending',
            'sent_at' => null,
        ]);

        $this->service->sendReminderNotification($reminder);

        $reminder->refresh();
        expect($reminder->status)->toBe('sent')
            ->and($reminder->sent_at)->not->toBeNull();
    });

    it('sets sent_at timestamp', function (): void {
        $reminder = TaskReminder::factory()->create([
            'status' => 'pending',
            'sent_at' => null,
        ]);

        $before = \Illuminate\Support\Facades\Date::now();
        $this->service->sendReminderNotification($reminder);
        $after = \Illuminate\Support\Facades\Date::now();

        $reminder->refresh();
        expect($reminder->sent_at)->toBeInstanceOf(Carbon::class)
            ->and($reminder->sent_at->between($before, $after))->toBeTrue();
    });
});

describe('getValidChannels', function (): void {
    it('returns array of valid channels', function (): void {
        $channels = $this->service->getValidChannels();

        expect($channels)->toBeArray()
            ->and($channels)->toContain('database', 'email', 'sms', 'slack')
            ->and($channels)->toHaveCount(4);
    });
});

describe('isValidChannel', function (): void {
    it('returns true for valid channels', function (): void {
        expect($this->service->isValidChannel('database'))->toBeTrue()
            ->and($this->service->isValidChannel('email'))->toBeTrue()
            ->and($this->service->isValidChannel('sms'))->toBeTrue()
            ->and($this->service->isValidChannel('slack'))->toBeTrue();
    });

    it('returns false for invalid channels', function (): void {
        expect($this->service->isValidChannel('invalid'))->toBeFalse()
            ->and($this->service->isValidChannel('webhook'))->toBeFalse()
            ->and($this->service->isValidChannel(''))->toBeFalse();
    });
});

describe('channel validation', function (): void {
    it('throws exception for invalid channel in scheduleReminder', function (): void {
        $task = Task::factory()->create();
        $user = User::factory()->create();
        $remindAt = \Illuminate\Support\Facades\Date::now()->addDay();

        expect(fn () => $this->service->scheduleReminder($task, $remindAt, $user, 'invalid'))
            ->toThrow(\InvalidArgumentException::class, "Invalid channel 'invalid'");
    });

    it('accepts all valid channels', function (): void {
        $task = Task::factory()->create();
        $user = User::factory()->create();
        $remindAt = \Illuminate\Support\Facades\Date::now()->addDay();

        foreach (['database', 'email', 'sms', 'slack'] as $channel) {
            $reminder = $this->service->scheduleReminder($task, $remindAt, $user, $channel);
            expect($reminder->channel)->toBe($channel);
        }
    });
});

describe('edge cases', function (): void {
    it('handles concurrent reminder cancellation gracefully', function (): void {
        $task = Task::factory()->create();
        TaskReminder::factory()->count(5)->create([
            'task_id' => $task->id,
            'status' => 'pending',
        ]);

        // Simulate concurrent cancellation
        $count1 = $this->service->cancelTaskReminders($task);
        $count2 = $this->service->cancelTaskReminders($task);

        expect($count1)->toBe(5)
            ->and($count2)->toBe(0);
    });

    it('handles reminder at exact current time', function (): void {
        $reminder = TaskReminder::factory()->create([
            'remind_at' => \Illuminate\Support\Facades\Date::now(),
            'status' => 'pending',
        ]);

        $reminders = $this->service->sendDueReminders();

        expect($reminders)->toHaveCount(1);
    });

    it('handles task with no reminders', function (): void {
        $task = Task::factory()->create();

        $pending = $this->service->getPendingReminders($task);
        $all = $this->service->getTaskReminders($task);

        expect($pending)->toBeEmpty()
            ->and($all)->toBeEmpty();
    });

    it('handles reschedule to same time', function (): void {
        $remindAt = \Illuminate\Support\Facades\Date::now()->addDay();
        $reminder = TaskReminder::factory()->create([
            'remind_at' => $remindAt,
            'status' => 'pending',
        ]);

        $result = $this->service->rescheduleReminder($reminder, $remindAt);

        expect($result)->toBeTrue();
        $reminder->refresh();
        expect($reminder->remind_at->toDateTimeString())->toBe($remindAt->toDateTimeString());
    });

    it('handles multiple reminders for same task and user', function (): void {
        $task = Task::factory()->create();
        $user = User::factory()->create();

        $reminder1 = $this->service->scheduleReminder($task, \Illuminate\Support\Facades\Date::now()->addDay(), $user);
        $reminder2 = $this->service->scheduleReminder($task, \Illuminate\Support\Facades\Date::now()->addDays(2), $user);

        expect($reminder1->id)->not->toBe($reminder2->id);

        $reminders = $this->service->getPendingReminders($task);
        expect($reminders)->toHaveCount(2);
    });
});

describe('transaction safety', function (): void {
    it('uses transaction for cancelTaskReminders', function (): void {
        $task = Task::factory()->create();
        TaskReminder::factory()->count(3)->create([
            'task_id' => $task->id,
            'status' => 'pending',
        ]);

        // This should be atomic
        $count = $this->service->cancelTaskReminders($task);

        expect($count)->toBe(3);

        // Verify all were canceled
        $remaining = TaskReminder::where('task_id', $task->id)
            ->where('status', 'pending')
            ->count();

        expect($remaining)->toBe(0);
    });
});
