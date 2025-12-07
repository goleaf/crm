<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\ActivityAlertNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('respects notification channel preferences', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $preference = $user->ensureNotificationPreference();
    $preference->update([
        'in_app' => true,
        'email' => false,
        'realtime' => true,
        'activity_alerts' => true,
    ]);

    app(NotificationService::class)->sendActivityAlert(
        $user,
        'Order updated',
        'Order ORD-1 moved forward',
        url: '/orders/1'
    );

    Notification::assertSentTo(
        $user,
        ActivityAlertNotification::class,
        function (ActivityAlertNotification $notification, array $channels) {
            expect($channels)->toContain('database')
                ->and($channels)->toContain('broadcast')
                ->and($channels)->not()->toContain('mail');

            return true;
        }
    );
});

it('skips alerts when activity notifications are disabled', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $user->ensureNotificationPreference()->update([
        'activity_alerts' => false,
    ]);

    app(NotificationService::class)->sendActivityAlert(
        $user,
        'Update',
        'Body'
    );

    Notification::assertNothingSent();
});
