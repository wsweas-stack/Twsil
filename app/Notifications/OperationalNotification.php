<?php

namespace App\Notifications;

use App\Enums\ShipmentStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OperationalNotification extends Notification
{
    use Queueable;

    /** @param array<string, string|int> $replace */
    public function __construct(
        private readonly string $event,
        private readonly string $titleKey,
        private readonly string $messageKey,
        private readonly ?string $url = null,
        private readonly array $replace = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'title_key' => $this->titleKey,
            'message_key' => $this->messageKey,
            'replace' => $this->replace,
            'url' => $this->url,
            'created_at' => now()->toIso8601String(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = __($this->titleKey, $this->replace);
        $message = __($this->messageKey, $this->replace);

        $mail = (new MailMessage)
            ->subject($title)
            ->greeting($title)
            ->line($message);

        if ($this->url !== null) {
            $mail->action(__('app.notifications.view_details'), $this->url);
        }

        return $mail;
    }

    /** @param array<string, mixed> $data
     *  @return array{title: string, message: string}
     */
    public static function localizedContent(array $data): array
    {
        $title = $data['title'] ?? __('app.notifications.update');
        $message = $data['message'] ?? '';
        $replace = is_array($data['replace'] ?? null) ? $data['replace'] : [];

        if (isset($data['title_key']) && is_string($data['title_key'])) {
            $title = __($data['title_key'], $replace);
        }

        if (isset($data['message_key']) && is_string($data['message_key'])) {
            $message = __($data['message_key'], $replace);
        }

        if (isset($data['title_key'], $data['message_key'])) {
            return ['title' => $title, 'message' => $message];
        }

        [$titleKey, $messageKey, $legacyReplace] = self::legacyTranslation($data);

        if ($titleKey !== null) {
            $title = __('app.notifications.' . $titleKey);
        }

        if ($messageKey !== null) {
            $message = __('app.notifications.' . $messageKey, $legacyReplace);
        }

        return ['title' => $title, 'message' => $message];
    }

    /** @param array<string, mixed> $data
     *  @return array{?string, ?string, array<string, string>}
     */
    private static function legacyTranslation(array $data): array
    {
        $event = $data['event'] ?? null;
        $message = is_string($data['message'] ?? null) ? $data['message'] : '';
        $titleKeys = [
            'registration_received' => 'registration_subject',
            'staff_approved' => 'staff_approved_subject',
            'staff_rejected' => 'staff_rejected_subject',
            'shipment_created' => 'shipment_created_subject',
            'driver_assigned' => 'driver_assigned_subject',
            'shipment_status_changed' => 'shipment_status_subject',
            'payment_confirmed' => 'payment_confirmed_subject',
            'delivery_confirmed' => 'delivery_confirmed_subject',
        ];

        if (! is_string($event) || ! isset($titleKeys[$event])) {
            return [null, null, []];
        }

        if ($event === 'registration_received') {
            foreach (['customer_registered', 'staff_registration_received'] as $messageKey) {
                foreach (['ar', 'en'] as $locale) {
                    if ($message === __($messageKey, [], $locale)) {
                        return [$titleKeys[$event], $messageKey, []];
                    }
                }
            }

            return [$titleKeys[$event], null, []];
        }

        if ($event === 'staff_approved') {
            return [$titleKeys[$event], 'staff_approved', []];
        }

        if ($event === 'staff_rejected') {
            foreach ([
                'لم تتم الموافقة على طلبك. السبب: ' => 'ar',
                'Your team application was not approved. Reason: ' => 'en',
            ] as $prefix => $locale) {
                if (str_starts_with($message, $prefix)) {
                    return [$titleKeys[$event], 'staff_rejected', ['reason' => substr($message, strlen($prefix))]];
                }
            }

            return [$titleKeys[$event], null, []];
        }

        if ($event === 'shipment_status_changed') {
            $pattern = '/^(?:أصبحت حالة الشحنة|Shipment)\s+([A-Za-z0-9_-]+)\s+(?:الآن|is now)\s+(.+?)\.?$/u';

            if (preg_match($pattern, $message, $matches) === 1) {
                foreach (ShipmentStatus::cases() as $status) {
                    foreach (['ar', 'en'] as $locale) {
                        $statusKey = 'app.shipment_statuses.' . $status->value;

                        if ($matches[2] === __($statusKey, [], $locale)) {
                            return [
                                $titleKeys[$event],
                                'shipment_status_changed',
                                ['tracking' => $matches[1], 'status' => __($statusKey)],
                            ];
                        }
                    }
                }
            }

            return [$titleKeys[$event], null, []];
        }

        $messageKeys = [
            'shipment_created' => 'shipment_created',
            'driver_assigned' => 'driver_assigned',
            'payment_confirmed' => 'payment_confirmed',
            'delivery_confirmed' => 'delivery_confirmed',
        ];

        if (preg_match('/(?:الشحنة|شحنة)\s+([A-Za-z0-9_-]+)/u', $message, $matches) !== 1
            && preg_match('/shipment\s+([A-Za-z0-9_-]+)/i', $message, $matches) !== 1) {
            return [$titleKeys[$event], null, []];
        }

        return [$titleKeys[$event], $messageKeys[$event], ['tracking' => $matches[1]]];
    }
}