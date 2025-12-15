<?php

declare(strict_types=1);

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Wraps a Filament notification for real-time broadcasting via Laravel Echo.
 */
final class RealTimeFilamentNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly FilamentNotification $filamentNotification) {}

    public function via(object $notifiable): array
    {
        return ['broadcast'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return $this->filamentNotification->getBroadcastMessage();
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
