<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Notifications\ActivityAlertNotification;

final class NotificationService
{
    public function sendActivityAlert(User $user, string $title, string $message, ?string $url = null): void
    {
        $preference = $user->ensureNotificationPreference();

        if (! $preference->activity_alerts) {
            return;
        }

        $channels = [];

        if ($preference->in_app) {
            $channels[] = 'database';
        }

        if ($preference->email) {
            $channels[] = 'mail';
        }

        if ($preference->realtime) {
            $channels[] = 'broadcast';
        }

        if ($channels === []) {
            return;
        }

        $user->notify(new ActivityAlertNotification(
            title: $title,
            message: $message,
            url: $url,
            channels: $channels
        ));
    }
}
