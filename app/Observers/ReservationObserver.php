<?php

namespace App\Observers;

use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationLifecycleNotification;
use App\Notifications\StaffActivityNotification;

class ReservationObserver
{
    public function updated(Reservation $reservation): void
    {
        $event = null;
        $staffEvent = null;

        if ($reservation->wasChanged('status')) {
            $event = match ($reservation->status) {
                'confirmed' => 'confirmed',
                'ready_for_pickup' => 'ready_for_pickup',
                'cancelled' => 'cancelled',
                'returned' => 'returned',
                default => null,
            };

            $staffEvent = match ($reservation->status) {
                'cancelled' => 'reservation_cancelled',
                'returned' => 'gown_returned',
                default => null,
            };

            if ($reservation->status === 'returned') {
                $this->notifyStaff($reservation, 'gown_returned', ['owner', 'employee']);

                $processedBy = $reservation->gownReturn()->with('processedBy')->first()?->processedBy;
                if ($processedBy?->role === 'employee') {
                    $this->notifyStaff($reservation, 'employee_activity', ['owner']);
                }
            }
        } elseif ($reservation->wasChanged(['pickup_date', 'return_date'])) {
            $event = 'updated';
            $staffEvent = 'reservation_updated';
        }

        if ($event !== null) {
            $this->notifyCustomer($reservation, $event);
        }

        if ($staffEvent === 'reservation_cancelled') {
            $this->notifyStaff($reservation, $staffEvent, ['owner']);
        } elseif ($staffEvent !== null && $staffEvent !== 'gown_returned') {
            $this->notifyStaff($reservation, $staffEvent, ['owner', 'employee']);
        }
    }

    private function notifyCustomer(Reservation $reservation, string $event): void
    {
        $user = $reservation->customer()->with('user')->first()?->user;

        if ($user?->role === 'customer') {
            $user->notify(new ReservationLifecycleNotification(
                $reservation->loadMissing('items.gown'),
                $event,
            ));
        }
    }

    private function notifyStaff(Reservation $reservation, string $event, array $roles): void
    {
        $reservation->loadMissing(['customer', 'items.gown']);
        User::whereIn('role', $roles)->get()->each(
            fn(User $user) => $user->notify(new StaffActivityNotification($event, $reservation))
        );
    }
}
