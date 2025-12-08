<?php

declare(strict_types=1);

namespace App\Support\ToastMagic;

use Devrabiul\ToastMagic\Facades\ToastMagic;
use Filament\Notifications\Notification;

final class ToastMagicNotifier
{
    public static function success(string $title, ?string $body = null, array $options = []): void
    {
        self::notify('success', $title, $body, $options);
    }

    public static function info(string $title, ?string $body = null, array $options = []): void
    {
        self::notify('info', $title, $body, $options);
    }

    public static function warning(string $title, ?string $body = null, array $options = []): void
    {
        self::notify('warning', $title, $body, $options);
    }

    public static function error(string $title, ?string $body = null, array $options = []): void
    {
        self::notify('error', $title, $body, $options);
    }

    public static function notify(string $status, string $title, ?string $body = null, array $options = []): void
    {
        $toastMethod = match ($status) {
            'error', 'danger' => 'error',
            'warning' => 'warning',
            'info' => 'info',
            default => 'success',
        };

        ToastMagic::dispatch()->{$toastMethod}($title, $body ?? '', $options);

        $notification = Notification::make()
            ->title($title)
            ->status($toastMethod === 'error' ? 'danger' : $toastMethod);

        if ($body !== null) {
            $notification->body($body);
        }

        $notification->send();
    }
}
