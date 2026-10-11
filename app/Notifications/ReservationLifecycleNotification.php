<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReservationLifecycleNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Reservation $reservation,
        public readonly string $event,
    )
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $gownName = $this->reservation->items->first()?->gown?->name ?? 'your gown';
        $reservationCode = $this->reservation->reservation_code;
        [$title, $message] = match ($this->event) {
            'confirmed' => [
                'Reservation confirmed',
                "Your reservation for {$gownName} ({$reservationCode}) has been confirmed.",
            ],
            'ready_for_pickup' => [
                'Ready for pickup',
                "Your reserved gown, {$gownName} ({$reservationCode}), is ready for pickup.",
            ],
            'pickup_reminder' => [
                'Pickup reminder',
                "Reminder: your pickup for {$gownName} ({$reservationCode}) is scheduled for tomorrow.",
            ],
            'return_reminder' => [
                'Return reminder',
                "Reminder: {$gownName} ({$reservationCode}) is due for return tomorrow.",
            ],
            'overdue' => [
                'Overdue return',
                "Your gown, {$gownName} ({$reservationCode}), is overdue for return. Please return it as soon as possible.",
            ],
            'returned' => [
                'Return recorded',
                "Your gown return for {$reservationCode} was recorded on "
                    .($this->reservation->gownReturn?->actual_return_date?->format('F j, Y') ?? 'today')
                    .' in '.ucfirst(str_replace('_', ' ', $this->reservation->gownReturn?->condition_after ?? 'good')).' condition.',
            ],
            'cancelled' => [
                'Reservation cancelled',
                "Your reservation for {$gownName} ({$reservationCode}) has been cancelled.",
            ],
            'updated' => [
                'Reservation updated',
                "Your reservation details for {$gownName} ({$reservationCode}) have been updated.",
            ],
            default => throw new \InvalidArgumentException("Unsupported reservation notification event: {$this->event}"),
        };

        return [
            'event' => $this->event,
            'title' => $title,
            'message' => $message,
            'reservation_id' => $this->reservation->id,
            'reservation_code' => $reservationCode,
            'url' => route('customer.reservations.show', $this->reservation),
        ];
    }
}
