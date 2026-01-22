<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class UniversalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $type;
    protected $title;
    protected $message;
    protected $url;
    protected $data;

    public function __construct(string $type, string $title, string $message, ?string $url = null, array $data = [])
    {
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
            'data' => $this->data,
        ];
    }

    protected function getIcon(): string
    {
        return match($this->type) {
            'order' => 'ci-package',
            'payment' => 'ci-credit-card',
            'product' => 'ci-shopping-bag',
            'system' => 'ci-bell',
            'promo' => 'ci-percent',
            'delivery' => 'ci-delivery',
            'review' => 'ci-star',
            default => 'ci-bell',
        };
    }

    protected function getColor(): string
    {
        return match($this->type) {
            'order' => 'primary',
            'payment' => 'success',
            'product' => 'info',
            'promo' => 'danger',
            'delivery' => 'warning',
            default => 'primary',
        };
    }

    public function toArray($notifiable)
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}