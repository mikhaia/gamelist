<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $event,
        private readonly string $message,
        private readonly string $url,
        private readonly string $icon,
        private readonly array $context = [],
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return array_merge([
            'event' => $this->event,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->icon,
        ], $this->context);
    }
}
