<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Gown;
use App\Models\Reservation;
use App\Models\GownReturn;
use App\Models\User;
use App\Notifications\ReservationLifecycleNotification;
use App\Notifications\StaffActivityNotification;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->customerUser = User::factory()->create(['role' => 'customer']);
    $this->customer = Customer::create([
        'user_id' => $this->customerUser->id,
        'customer_code' => 'CUS-NOTIFY-001',
        'full_name' => $this->customerUser->name,
        'contact_number' => '09171234567',
        'email' => $this->customerUser->email,
        'status' => 'active',
    ]);

    $category = Category::create(['name' => 'Formal gowns']);
    $this->gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-NOTIFY-001',
        'name' => 'Blue Evening Gown',
        'rental_price' => 2500,
        'status' => 'available',
    ]);

    $this->reservation = Reservation::create([
        'reservation_code' => 'GR-NOTIFY-001',
        'customer_id' => $this->customer->id,
        'pickup_date' => today()->addDays(2),
        'return_date' => today()->addDays(3),
        'rental_total' => 2500,
        'grand_total' => 2500,
        'balance' => 2500,
        'status' => 'pending',
    ]);
    $this->reservation->items()->create([
        'gown_id' => $this->gown->id,
        'rental_price' => 2500,
    ]);
});

it('notifies a customer when their reservation is confirmed and provides an inbox', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->patch(route('owner.reservations.update', $this->reservation), ['status' => 'confirmed'])
        ->assertRedirect();

    $notification = $this->customerUser->fresh()->notifications()->firstOrFail();
    expect($notification->data['event'])->toBe('confirmed')
        ->and($notification->data['message'])->toContain('Blue Evening Gown')
        ->and($notification->data['url'])->toContain(route('customer.reservations.show', $this->reservation));

    $this->actingAs($this->customerUser)
        ->get(route('customer.notifications'))
        ->assertOk()
        ->assertSee('Reservation confirmed')
        ->assertSee('Blue Evening Gown');

    $this->post(route('customer.notifications.read', ['notification' => $notification->id]))
        ->assertRedirect();

    expect($this->customerUser->fresh()->unreadNotifications()->count())->toBe(0);
});

it('notifies a customer when an owner changes their reservation dates', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->patch(route('owner.reservations.update', $this->reservation), [
            'status' => 'pending',
            'admin_notes' => 'Please confirm the updated dates.',
            'pickup_date' => today()->addDays(4)->toDateString(),
            'return_date' => today()->addDays(5)->toDateString(),
        ])
        ->assertRedirect();

    expect($this->customerUser->fresh()->notifications()->firstOrFail()->data['event'])
        ->toBe('updated');
});

it('rejects owner date changes that conflict with another reservation', function () {
    $otherReservation = Reservation::create([
        'reservation_code' => 'GR-NOTIFY-002',
        'customer_id' => $this->customer->id,
        'pickup_date' => today()->addDays(5),
        'return_date' => today()->addDays(6),
        'rental_total' => 2500,
        'grand_total' => 2500,
        'balance' => 2500,
        'status' => 'confirmed',
    ]);
    $otherReservation->items()->create([
        'gown_id' => $this->gown->id,
        'rental_price' => 2500,
    ]);
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->from(route('owner.reservations'))
        ->patch(route('owner.reservations.update', $this->reservation), [
            'status' => 'pending',
            'pickup_date' => today()->addDays(5)->toDateString(),
            'return_date' => today()->addDays(6)->toDateString(),
        ])
        ->assertSessionHasErrors('pickup_date');

    expect($this->reservation->fresh()->pickup_date->toDateString())
        ->toBe(today()->addDays(2)->toDateString());
});

it('allows status updates without changing an existing past reservation date', function () {
    $this->reservation->update([
        'pickup_date' => today()->subDays(2)->toDateString(),
        'return_date' => today()->subDays(1)->toDateString(),
    ]);
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->patch(route('owner.reservations.update', $this->reservation), [
            'status' => 'confirmed',
            'pickup_date' => today()->subDays(2)->toDateString(),
            'return_date' => today()->subDays(1)->toDateString(),
        ])
        ->assertRedirect();

    expect($this->reservation->fresh()->status)->toBe('confirmed');
});

it('notifies customers when a reservation is ready for pickup or cancelled', function () {
    $this->reservation->update(['status' => 'ready_for_pickup']);
    $this->reservation->update(['status' => 'cancelled']);

    expect($this->customerUser->fresh()->notifications()->where('data->event', 'ready_for_pickup')->exists())
        ->toBeTrue()
        ->and($this->customerUser->fresh()->notifications()->where('data->event', 'cancelled')->exists())
        ->toBeTrue();
});

it('sends pickup reminders once for reservations due tomorrow', function () {
    $this->reservation->update([
        'status' => 'confirmed',
        'pickup_date' => Carbon::tomorrow()->toDateString(),
    ]);

    $this->artisan('app:send-reservation-reminders')
        ->expectsOutput('Sent 1 reservation reminder notification(s).')
        ->assertSuccessful();
    $this->artisan('app:send-reservation-reminders')
        ->expectsOutput('Sent 0 reservation reminder notification(s).')
        ->assertSuccessful();

    expect($this->customerUser->fresh()->notifications()->count())->toBe(2)
        ->and($this->customerUser->fresh()->notifications()->where('data->event', 'pickup_reminder')->exists())
        ->toBeTrue();
});

it('sends return and overdue reminders to the customer', function () {
    $this->reservation->update([
        'status' => 'released',
        'return_date' => Carbon::tomorrow()->toDateString(),
    ]);
    $this->artisan('app:send-reservation-reminders')->assertSuccessful();

    $this->reservation->update([
        'status' => 'overdue',
        'return_date' => Carbon::yesterday()->toDateString(),
    ]);
    $this->artisan('app:send-reservation-reminders')->assertSuccessful();

    expect($this->customerUser->fresh()->notifications()->where('data->event', 'return_reminder')->exists())
        ->toBeTrue()
        ->and($this->customerUser->fresh()->notifications()->where('data->event', 'overdue')->exists())
        ->toBeTrue();
});

it('sends deduplicated pickup reminders to employees and owners', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $employee = User::factory()->create(['role' => 'employee']);
    $this->reservation->update([
        'status' => 'confirmed',
        'pickup_date' => Carbon::tomorrow()->toDateString(),
    ]);

    $this->artisan('app:send-reservation-reminders')->assertSuccessful();

    foreach ([$owner, $employee] as $staffUser) {
        expect($staffUser->fresh()->notifications()->where('data->event', 'pickup_reminder')->exists())
            ->toBeTrue();
    }

    $this->artisan('app:send-reservation-reminders')->assertSuccessful();

    foreach ([$owner, $employee] as $staffUser) {
        expect($staffUser->fresh()->notifications()->where('data->event', 'pickup_reminder')->count())
            ->toBe(1);
    }
});

it('notifies owners and employees when a customer submits a reservation', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $employee = User::factory()->create(['role' => 'employee']);
    $reservation = Reservation::create([
        'reservation_code' => 'GR-STAFF-001',
        'customer_id' => $this->customer->id,
        'pickup_date' => today()->addDays(7),
        'return_date' => today()->addDays(8),
        'rental_total' => 2500,
        'grand_total' => 2500,
        'balance' => 2500,
        'status' => 'pending',
    ]);
    $reservation->items()->create([
        'gown_id' => $this->gown->id,
        'rental_price' => 2500,
    ]);

    foreach ([$owner, $employee] as $staffUser) {
        $notification = $staffUser->fresh()->notifications()->where('data->event', 'new_reservation')->firstOrFail();
        expect($notification->data['message'])->toContain('Blue Evening Gown')
            ->and($notification->data['message'])->toContain($this->customer->full_name)
            ->and($notification->data['url'])->toContain('/' . $staffUser->role . '/reservations');
    }
});

it('notifies employees of reservation changes and owners of cancellations', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $employee = User::factory()->create(['role' => 'employee']);
    $this->reservation->update(['pickup_date' => today()->addDays(4)]);
    $this->reservation->update(['status' => 'cancelled']);

    expect($employee->fresh()->notifications()->where('data->event', 'reservation_updated')->exists())
        ->toBeTrue()
        ->and($employee->fresh()->notifications()->where('data->event', 'reservation_cancelled')->exists())
        ->toBeFalse()
        ->and($owner->fresh()->notifications()->where('data->event', 'reservation_cancelled')->exists())
        ->toBeTrue();
});

it('notifies employees and owners when a gown is returned and sends employee activity to owners', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $employee = User::factory()->create(['role' => 'employee']);
    GownReturn::create([
        'reservation_id' => $this->reservation->id,
        'processed_by' => $employee->id,
        'actual_return_date' => today(),
        'actual_return_time' => now()->format('H:i:s'),
        'condition_after' => 'good',
        'late_days' => 0,
    ]);
    $this->reservation->update(['status' => 'returned']);

    expect($owner->fresh()->notifications()->where('data->event', 'gown_returned')->exists())
        ->toBeTrue()
        ->and($employee->fresh()->notifications()->where('data->event', 'gown_returned')->exists())
        ->toBeTrue()
        ->and($owner->fresh()->notifications()->where('data->event', 'employee_activity')->exists())
        ->toBeTrue();
});

it('notifies owners when a customer registers and a gown status changes', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $newCustomer = User::factory()->create(['role' => 'customer']);
    $this->gown->update(['status' => 'damaged']);

    $gownStatusNotification = $owner->fresh()->notifications()
        ->where('data->event', 'gown_status_changed')
        ->firstOrFail();

    expect($owner->fresh()->notifications()->where('data->event', 'new_customer')->exists())
        ->toBeTrue()
        ->and($gownStatusNotification->data['message'])
        ->toContain('available to damaged')
        ->and($newCustomer->fresh()->notifications()->count())
        ->toBe(0);
});

it('provides private owner and employee notification inboxes', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $employee = User::factory()->create(['role' => 'employee']);
    $owner->notify(new StaffActivityNotification('new_reservation', $this->reservation));

    $this->actingAs($employee)
        ->get(route('employee.notifications'))
        ->assertOk()
        ->assertSee('Employee notifications')
        ->assertSee('No notifications yet');

    $notification = $owner->notifications()->firstOrFail();
    $this->post(route('employee.notifications.read', $notification->id))->assertNotFound();

    $this->actingAs($owner)
        ->get(route('owner.notifications'))
        ->assertOk()
        ->assertSee('Owner notifications')
        ->assertSee('New reservation');
});

it('links owner dashboard reservations to their detail pages', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $response = $this->actingAs($owner)
        ->get(route('owner.dashboard'))
        ->assertOk()
        ->assertSee('data-href="' . route('owner.reservations.show', $this->reservation) . '"', false)
        ->assertSee('View payments');

    $html = $response->getContent();
    $addGownLink = '~<a href="' . preg_quote(route('owner.gowns.create'), '~')
        . '"[^>]*>.*?Add New Gown\s*</a>~s';

    expect(preg_match($addGownLink, $html))->toBe(1);
});

it('keeps each recently added gown card image details and price inside its link', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $html = $this->actingAs($owner)->get(route('owner.dashboard'))->assertOk()->getContent();
    $start = strpos($html, '<a href="' . route('owner.gowns.show', $this->gown) . '"');

    expect($start)->not->toBeFalse();

    $end = strpos($html, '</a>', $start);
    $gownLink = substr($html, $start, $end - $start);

    expect($gownLink)
        ->toContain('Blue Evening Gown')
        ->toContain('GWN-NOTIFY-001')
        ->toContain('2,500');
});

it('does not expose customer notifications to another account', function () {
    $this->customerUser->notify(new ReservationLifecycleNotification($this->reservation, 'confirmed'));
    $otherCustomer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($otherCustomer)
        ->post(route('customer.notifications.read', ['notification' => $this->customerUser->notifications()->firstOrFail()->id]))
        ->assertNotFound();
});
