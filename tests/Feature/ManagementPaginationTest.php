<?php

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Reservation;
use App\Models\User;

it('paginates the reservation desk and handoff queue using separate page controls', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $customerUser = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create([
        'user_id' => $customerUser->id,
        'customer_code' => 'CUS-PAGE-TEST',
        'full_name' => 'Pagination Customer',
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);

    foreach (range(1, 13) as $number) {
        Reservation::create([
            'reservation_code' => sprintf('RES-PAGE-%03d', $number),
            'customer_id' => $customer->id,
            'pickup_date' => today(),
            'return_date' => today()->addDay(),
            'rental_total' => 1000,
            'grand_total' => 1000,
            'balance' => 0,
            'status' => 'confirmed',
        ]);
    }

    $this->actingAs($owner)
        ->get(route('owner.reservations', ['per_page' => 12]))
        ->assertOk()
        ->assertSee('Showing 1–12')
        ->assertSee('of 13 reservations')
        ->assertViewHas('reservations', fn($reservations) => $reservations->total() === 13
            && $reservations->count() === 12
            && $reservations->perPage() === 12);

    $this->get(route('owner.rentals', [
        'rentals_per_page' => 48,
        'cleanings_per_page' => 24,
    ]))
        ->assertOk()
        ->assertSee('data-href="' . route('owner.reservations.show', Reservation::where('reservation_code', 'RES-PAGE-001')->firstOrFail()) . '"', false)
        ->assertSee('Showing 1–13')
        ->assertSee('of 13 records')
        ->assertViewHas('rentals', fn($rentals) => $rentals->total() === 13
            && $rentals->count() === 13
            && $rentals->perPage() === 48)
        ->assertViewHas('cleanings', fn($cleanings) => $cleanings->perPage() === 24);

    $this->actingAs($customerUser)
        ->get(route('customer.reservations', ['per_page' => 12]))
        ->assertOk()
        ->assertSee('Showing 1–12')
        ->assertSee('of 13 reservations')
        ->assertViewHas('reservations', fn($reservations) => $reservations->total() === 13
            && $reservations->count() === 12
            && $reservations->perPage() === 12);
});

it('paginates employee work activity even when the activity log is empty', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $employeeUser = User::factory()->create(['role' => 'employee']);
    $employee = Employee::create([
        'user_id' => $employeeUser->id,
        'employee_code' => 'EMP-PAGE-TEST',
        'full_name' => $employeeUser->name,
        'position' => 'Rental Staff',
        'status' => 'active',
    ]);

    $this->actingAs($owner)
        ->get(route('owner.employees.show', $employee))
        ->assertOk()
        ->assertViewHas('activities', fn($activities) => $activities->total() === 0
            && $activities->perPage() === 12);
});
