<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeliveryOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly string $trackingNumber,
        private readonly int $expiresInMinutes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('app.notifications.delivery_otp_subject'))
            ->greeting(__('app.notifications.delivery_otp_greeting'))
            ->line(__('app.notifications.delivery_otp_intro', ['tracking' => $this->trackingNumber]))
            ->line($this->code)
            ->line(__('app.notifications.delivery_otp_expiry', ['minutes' => $this->expiresInMinutes]))
            ->line(__('app.notifications.delivery_otp_security'));
    }
}