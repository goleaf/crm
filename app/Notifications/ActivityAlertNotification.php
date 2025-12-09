<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ActivityAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param list<string> $channels
     */
    public function __construct(
        public string $title,
        public string $message,
        public ?string $url = null,
        private array $channels = ['database', 'mail', 'broadcast'],
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->line($this->message);

        if ($this->url !== null) {
            $mail->action('View', $this->url);
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'type' => 'activity_alert',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function broadcastOn(?object $notifiable = null): array
    {
        return [new PrivateChannel('App.Models.User.' . $notifiable->getKey())];
    }

    public function broadcastType(): string
    {
        return 'activity.alert';
    }
}
