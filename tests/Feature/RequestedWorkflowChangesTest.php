<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\CleaningRecord;
use App\Models\Customer;
use App\Models\DamageReport;
use App\Models\Employee;
use App\Models\Gown;
use App\Models\GownPurchase;
use App\Models\MaintenanceRecord;
use App\Models\Penalty;
use App\Models\Reservation;
use App\Models\User;

it('keeps linked reservation names current when customers update their profile', function () {
    $user = User::factory()->create(['role' => 'customer', 'name' => 'Former Customer']);
    $customer = Customer::create([
        'user_id' => $user->id,
        'customer_code' => 'CUS-NAME-001',
        'full_name' => $user->name,
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);

    foreach (['cancelled', 'completed'] as $index => $status) {
        Reservation::create([
            'reservation_code' => 'SB-NAME-'.$index,
            'customer_id' => $customer->id,
            'pickup_date' => today(),
            'return_date' => today(),
            'rental_total' => 1000,
            'grand_total' => 1000,
            'balance' => 0,
            'status' => $status,
        ]);
    }

    $this->actingAs($user)->patch('/profile', [
        'name' => 'Updated Customer',
        'email' => $user->email,
    ])->assertSessionHasNoErrors();

    expect($customer->fresh()->full_name)->toBe('Updated Customer')
        ->and(Reservation::with('customer')->get()->pluck('customer.full_name')->unique()->all())
        ->toBe(['Updated Customer']);
});

it('keeps employee directory names current when employees update their profile', function () {
    $user = User::factory()->create(['role' => 'employee', 'name' => 'Former Employee']);
    $employee = Employee::create([
        'user_id' => $user->id,
        'employee_code' => 'EMP-NAME-001',
        'full_name' => $user->name,
        'status' => 'active',
    ]);

    $this->actingAs($user)->patch('/profile', [
        'name' => 'Updated Employee',
        'email' => $user->email,
    ])->assertSessionHasNoErrors();

    expect($employee->fresh()->full_name)->toBe('Updated Employee')
        ->and($user->fresh()->name)->toBe('Updated Employee');
});

it('updates a customers name on every linked transaction from management', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $customer = Customer::create([
        'customer_code' => 'CUS-NAME-002',
        'full_name' => 'Test Customer',
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);
    $reservation = Reservation::create([
        'reservation_code' => 'SB-NAME-OLD',
        'customer_id' => $customer->id,
        'pickup_date' => today(),
        'return_date' => today(),
        'rental_total' => 1000,
        'grand_total' => 1000,
        'balance' => 0,
        'status' => 'cancelled',
    ]);

    $this->actingAs($owner)
        ->patch(route('owner.customers.update', $customer), ['full_name' => 'Corrected Customer'])
        ->assertSessionHasNoErrors();

    $this->actingAs($owner)
        ->get(route('owner.customers.show', $customer))
        ->assertOk()
        ->assertSee('Corrected Customer')
        ->assertSee($reservation->reservation_code);

    expect($reservation->fresh()->customer->full_name)->toBe('Corrected Customer');
});

it('renders historical approval actors using their current employee name', function () {
    $user = User::factory()->create(['role' => 'employee', 'name' => 'Old Staff Name']);
    $audit = AuditLog::create([
        'user_id' => $user->id,
        'action' => 'reservation.approved',
        'description' => 'Reservation SB-HISTORY-01: Approved by Old Staff Name.',
    ]);

    $user->update(['name' => 'Current Staff Name']);

    expect($audit->fresh()->display_description)
        ->toBe('Reservation SB-HISTORY-01: Approved by Current Staff Name.');
});

it('records customer gown purchases and removes sold gowns from rental inventory', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $customerUser = User::factory()->create(['role' => 'customer', 'name' => 'Purchase Customer']);
    $customer = Customer::create([
        'user_id' => $customerUser->id,
        'customer_code' => 'CUS-SALE-001',
        'full_name' => 'Purchase Customer',
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);
    $category = Category::create(['name' => 'Sale gowns']);
    $gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-SALE-001',
        'name' => 'Purchased gown',
        'rental_price' => 2000,
        'security_deposit' => 0,
        'status' => 'available',
    ]);
    $reservation = Reservation::create([
        'reservation_code' => 'SB-SALE-001',
        'customer_id' => $customer->id,
        'pickup_date' => today(),
        'return_date' => today(),
        'rental_total' => 2000,
        'grand_total' => 2000,
        'amount_paid' => 0,
        'balance' => 2000,
        'status' => 'released',
    ]);
    $reservation->items()->create([
        'gown_id' => $gown->id,
        'rental_price' => 2000,
        'security_deposit' => 0,
        'quantity' => 1,
    ]);

    $this->actingAs($owner)
        ->post(route('owner.rentals.return', $reservation), [
            'actual_return_date' => today()->toDateString(),
            'condition_after' => 'damaged',
            'repair_cost' => 500,
            'purchased_gown_ids' => [$gown->id],
            'purchase_amounts' => [$gown->id => 3500],
            'notes' => 'Customer purchased the damaged gown.',
        ])
        ->assertSessionHasNoErrors();

    expect($gown->fresh()->status)->toBe('retired')
        ->and($gown->fresh()->condition)->toBe('damaged')
        ->and(GownPurchase::where('reservation_id', $reservation->id)->value('amount'))->toBe('3500.00')
        ->and((float) $reservation->fresh()->grand_total)->toBe(5500.0)
        ->and((float) $reservation->fresh()->balance)->toBe(5500.0)
        ->and(Penalty::where('reservation_id', $reservation->id)->pluck('penalty_type')->all())
        ->toBe(['gown_purchase']);

    $this->actingAs($customerUser)
        ->get(route('customer.reservations.show', $reservation))
        ->assertOk()
        ->assertSee('₱5,500.00')
        ->assertDontSee('₱9,000.00');
});

it('requires an agreed sale amount before accepting a customer gown purchase', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $customer = Customer::create([
        'customer_code' => 'CUS-SALE-002',
        'full_name' => 'Purchase Customer Two',
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);
    $category = Category::create(['name' => 'Sale gowns']);
    $gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-SALE-002',
        'name' => 'Second gown',
        'rental_price' => 1500,
        'security_deposit' => 0,
        'status' => 'available',
    ]);
    $reservation = Reservation::create([
        'reservation_code' => 'SB-SALE-002',
        'customer_id' => $customer->id,
        'pickup_date' => today(),
        'return_date' => today(),
        'rental_total' => 1500,
        'grand_total' => 1500,
        'balance' => 1500,
        'status' => 'released',
    ]);
    $reservation->items()->create([
        'gown_id' => $gown->id,
        'rental_price' => 1500,
        'security_deposit' => 0,
        'quantity' => 1,
    ]);

    $this->actingAs($owner)
        ->from(route('owner.rentals'))
        ->post(route('owner.rentals.return', $reservation), [
            'actual_return_date' => today()->toDateString(),
            'condition_after' => 'good',
            'purchased_gown_ids' => [$gown->id],
        ])
        ->assertSessionHasErrors('purchase_amounts.'.$gown->id);

    expect($reservation->fresh()->status)->toBe('released')
        ->and($gown->fresh()->status)->toBe('available')
        ->and(GownPurchase::where('reservation_id', $reservation->id)->exists())->toBeFalse();
});

it('keeps damaged gowns out of rental inventory and records their repair charge', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $customer = Customer::create([
        'customer_code' => 'CUS-DAMAGE-001',
        'full_name' => 'Damage Customer',
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);
    $category = Category::create(['name' => 'Damaged gowns']);
    $gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-DAMAGE-001',
        'name' => 'Damaged gown',
        'rental_price' => 1800,
        'security_deposit' => 0,
        'status' => 'available',
    ]);
    $reservation = Reservation::create([
        'reservation_code' => 'SB-DAMAGE-001',
        'customer_id' => $customer->id,
        'pickup_date' => today(),
        'return_date' => today(),
        'rental_total' => 1800,
        'grand_total' => 1800,
        'balance' => 1800,
        'status' => 'released',
    ]);
    $reservation->items()->create([
        'gown_id' => $gown->id,
        'rental_price' => 1800,
        'security_deposit' => 0,
        'quantity' => 1,
    ]);

    $this->actingAs($owner)
        ->post(route('owner.rentals.return', $reservation), [
            'actual_return_date' => today()->toDateString(),
            'condition_after' => 'damaged',
            'repair_cost' => 650,
        ])
        ->assertSessionHasNoErrors();

    expect($gown->fresh()->status)->toBe('damaged')
        ->and(DamageReport::where('gown_id', $gown->id)->exists())->toBeTrue()
        ->and(Penalty::where('reservation_id', $reservation->id)->value('penalty_type'))->toBe('damage_fee')
        ->and((float) $reservation->fresh()->balance)->toBe(2450.0)
        ->and(GownPurchase::where('reservation_id', $reservation->id)->exists())->toBeFalse();

    $this->get(route('owner.damages'))
        ->assertOk()
        ->assertSee('Damaged gown')
        ->assertSee('Rental return damage')
        ->assertSee('Damage recorded during return inspection.');
});

it('sends returned gowns marked for repair to maintenance until the job is completed', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $customerUser = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create([
        'user_id' => $customerUser->id,
        'customer_code' => 'CUS-REPAIR-001',
        'full_name' => $customerUser->name,
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);
    $category = Category::create(['name' => 'Repair gowns']);
    $gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-REPAIR-001',
        'name' => 'Gown needing repair',
        'rental_price' => 1800,
        'security_deposit' => 0,
        'status' => 'available',
    ]);
    $reservation = Reservation::create([
        'reservation_code' => 'SB-REPAIR-001',
        'customer_id' => $customer->id,
        'pickup_date' => today()->subDays(3),
        'return_date' => today()->subDay(),
        'rental_total' => 1800,
        'grand_total' => 1800,
        'balance' => 0,
        'status' => 'released',
    ]);
    $reservation->items()->create([
        'gown_id' => $gown->id,
        'rental_price' => 1800,
        'security_deposit' => 0,
        'quantity' => 1,
    ]);

    $this->actingAs($owner)
        ->get(route('owner.rentals'))
        ->assertOk()
        ->assertSee('<option value="repair"', false)
        ->assertSee('>Repair</option>', false)
        ->assertDontSee('<option value="fair"', false);

    $this->post(route('owner.rentals.return', $reservation), [
        'actual_return_date' => today()->toDateString(),
        'condition_after' => 'repair',
        'repair_cost' => 450,
        'notes' => 'Loose beading needs repair.',
    ])->assertSessionHasNoErrors();

    $maintenance = MaintenanceRecord::where('gown_id', $gown->id)->firstOrFail();
    expect($reservation->fresh()->gownReturn->condition_after)->toBe('repair')
        ->and($gown->fresh()->status)->toBe('under_maintenance')
        ->and($maintenance->maintenance_type)->toBe('Repair')
        ->and($maintenance->description)->toBe('Loose beading needs repair.')
        ->and($maintenance->status)->toBe('pending')
        ->and((float) $maintenance->cost)->toBe(450.0)
        ->and(CleaningRecord::where('gown_id', $gown->id)->exists())->toBeFalse();

    $this->actingAs($owner)
        ->get(route('owner.catalog'))
        ->assertOk()
        ->assertSee('class="sb-product-badge is-under_maintenance"', false)
        ->assertSee('Repair');

    $this->actingAs($customerUser)
        ->getJson(route('customer.reserve.availability', $gown).'?'.http_build_query([
            'pickup_date' => today()->addDays(2)->toDateString(),
            'return_date' => today()->addDays(2)->toDateString(),
        ]))
        ->assertNotFound();

    $this->actingAs($owner)
        ->post(route('owner.maintenance.complete', $maintenance), [
            'condition' => 'good',
            'completion_notes' => 'Beading repaired and inspected.',
        ])
        ->assertRedirect();

    expect($maintenance->fresh()->status)->toBe('completed')
        ->and($gown->fresh()->status)->toBe('available');
});

it('records the inspected return date and notifies the customer, employee, and owner', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $employee = User::factory()->create(['role' => 'employee']);
    $customerUser = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create([
        'user_id' => $customerUser->id,
        'customer_code' => 'CUS-RETURN-001',
        'full_name' => 'Return Customer',
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);
    $category = Category::create(['name' => 'Return gowns']);
    $gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-RETURN-001',
        'name' => 'Returned gown',
        'rental_price' => 1800,
        'security_deposit' => 0,
        'status' => 'available',
    ]);
    $reservation = Reservation::create([
        'reservation_code' => 'SB-RETURN-001',
        'customer_id' => $customer->id,
        'pickup_date' => today()->subDays(3),
        'return_date' => today()->subDay(),
        'rental_total' => 1800,
        'grand_total' => 1800,
        'balance' => 0,
        'status' => 'released',
    ]);
    $reservation->items()->create([
        'gown_id' => $gown->id,
        'rental_price' => 1800,
        'security_deposit' => 0,
        'quantity' => 1,
    ]);

    $this->actingAs($employee)
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertSee('Record Return')
        ->assertSee(route('employee.rentals').'#rental-row-'.$reservation->id, false);

    $this->get(route('employee.rentals'))
        ->assertOk()
        ->assertSee('Record Return')
        ->assertSee(route('employee.rentals.return', $reservation), false)
        ->assertSee('name="actual_return_date"', false)
        ->assertSee('name="condition_after"', false)
        ->assertSee('Confirm and record return');

    $this->get(route('employee.reservations.show', $reservation))
        ->assertOk()
        ->assertSee('Return inspection')
        ->assertSee('actual_return_date', false)
        ->assertSee('Confirm and record return');

    $this->actingAs($employee)
        ->get(route('employee.reservations.show', $reservation))
        ->assertOk()
        ->assertSee('Confirm gown return')
        ->assertSee('Actual return date')
        ->assertSee('Return Customer');

    $this->from(route('employee.reservations.show', $reservation))
        ->post(route('employee.rentals.return', $reservation), [
            'actual_return_date' => today()->addDay()->toDateString(),
            'condition_after' => 'good',
        ])
        ->assertSessionHasErrors('actual_return_date');

    $this->post(route('employee.rentals.return', $reservation), [
        'actual_return_date' => today()->toDateString(),
        'condition_after' => 'good',
        'notes' => 'Small stain on the hem.',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('employee.rentals'));

    $return = $reservation->fresh()->gownReturn;
    $customerNotice = $customerUser->fresh()->notifications()->where('data->event', 'returned')->firstOrFail();
    $staffNotice = $owner->fresh()->notifications()->where('data->event', 'gown_returned')->firstOrFail();

    expect($reservation->fresh()->status)->toBe('returned')
        ->and($return->actual_return_date->toDateString())->toBe(today()->toDateString())
        ->and($return->late_days)->toBe(1)
        ->and($gown->fresh()->status)->toBe('for_cleaning')
        ->and($customerNotice->data['message'])->toContain('Good condition')
        ->and($staffNotice->data['message'])->toContain('Good condition')
        ->and($employee->fresh()->notifications()->where('data->event', 'gown_returned')->exists())->toBeTrue()
        ->and($owner->fresh()->notifications()->where('data->event', 'employee_activity')->exists())->toBeTrue();

    $cleaning = CleaningRecord::where('gown_id', $gown->id)->firstOrFail();
    $this->actingAs($employee)
        ->get(route('employee.rentals'))
        ->assertOk()
        ->assertSee('Gowns to clean')
        ->assertSee('Mark clean & available');

    $this->from(route('employee.rentals'))
        ->post(route('employee.cleaning.complete', $cleaning))
        ->assertRedirect(route('employee.rentals'));

    expect($cleaning->fresh()->status)->toBe('completed')
        ->and($gown->fresh()->status)->toBe('available');

    $this->actingAs($customerUser)
        ->get(route('customer.reservations.show', $reservation))
        ->assertOk()
        ->assertSee('Gown returned')
        ->assertSee('Small stain on the hem.');

    $this->actingAs($employee)
        ->get(route('employee.rentals'))
        ->assertOk()
        ->assertSee('View Return Details')
        ->assertSee('Returned '.today()->format('M d, Y'));

    $reservation->update(['collateral_status' => 'held', 'balance' => 125]);

    foreach ([$employee, $owner] as $staffUser) {
        $this->actingAs($staffUser)
            ->get(route($staffUser->role.'.rentals'))
            ->assertOk()
            ->assertDontSee('View Return Details')
            ->assertSee('Release ID')
            ->assertSee('Clear ₱125.00 before returning the held ID.')
            ->assertSee('data-row-nav-ignore', false)
            ->assertSee(route($staffUser->role.'.payments', ['reservation_id' => $reservation->id]), false);

        $this->actingAs($staffUser)
            ->get(route($staffUser->role.'.reservations.show', $reservation))
            ->assertOk()
            ->assertSee("Return the customer's ID", false)
            ->assertSee('ID is still held. Clear ₱125.00 before releasing it.');
    }

    $this->actingAs($employee)
        ->post(route('employee.rentals.release-id', $reservation))
        ->assertStatus(422);

    $this->actingAs($employee)
        ->get(route('employee.payments', ['reservation_id' => $reservation->id]))
        ->assertOk()
        ->assertSee('data-record-payment-dialog', false)
        ->assertSee('value="'.$reservation->id.'"', false);

    $reservation->update(['balance' => 0]);
    $this->actingAs($owner)
        ->get(route('owner.rentals'))
        ->assertOk()
        ->assertSee(route('owner.rentals.release-id', $reservation), false)
        ->assertSee('Release ID')
        ->assertDontSee('View Return Details');

    $this->actingAs($employee)
        ->from(route('employee.reservations.show', $reservation))
        ->get(route('employee.reservations.show', $reservation))
        ->assertOk()
        ->assertSee(route('employee.rentals.release-id', $reservation), false)
        ->assertSee('Release ID to customer');

    $this->actingAs($employee)
        ->from(route('employee.reservations.show', $reservation))
        ->post(route('employee.rentals.release-id', $reservation))
        ->assertRedirect(route('employee.reservations.show', $reservation));
    expect($reservation->fresh()->collateral_status)->toBe('released');

    $this->actingAs($employee)
        ->get(route('employee.rentals'))
        ->assertOk()
        ->assertSee('View Return Details')
        ->assertSee(route('employee.reservations.show', $reservation).'#return-details', false);
});

it('allows staff to record an early physical handoff without marking the gown as rented', function () {
    $employee = User::factory()->create(['role' => 'employee']);
    $customer = Customer::create([
        'customer_code' => 'CUS-EARLY-PICKUP',
        'full_name' => 'Early Pickup Customer',
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);
    $category = Category::create(['name' => 'Early pickup gowns']);
    $gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-EARLY-PICKUP',
        'name' => 'Early pickup gown',
        'rental_price' => 1800,
        'security_deposit' => 0,
        'status' => 'reserved',
    ]);
    $reservation = Reservation::create([
        'reservation_code' => 'SB-EARLY-PICKUP',
        'customer_id' => $customer->id,
        'pickup_date' => today()->addDays(3),
        'return_date' => today()->addDays(5),
        'rental_total' => 1800,
        'grand_total' => 1800,
        'balance' => 0,
        'status' => 'ready_for_pickup',
        'physical_id_photo_path' => 'government-ids/test-id.jpg',
    ]);
    $reservation->items()->create([
        'gown_id' => $gown->id,
        'rental_price' => 1800,
        'security_deposit' => 0,
        'quantity' => 1,
    ]);

    $this->actingAs($employee)
        ->get(route('employee.rentals'))
        ->assertOk()
        ->assertSee('Record pickup')
        ->assertSee('Scheduled pickup is '.today()->addDays(3)->format('M d, Y'));

    $this->actingAs($employee)
        ->post(route('employee.rentals.release', $reservation), [
            'condition_before' => 'good',
            'id_safe_slot' => 'Safe A · Slot 04',
        ])
        ->assertRedirect(route('employee.rentals'));

    expect($reservation->fresh()->status)->toBe('released')
        ->and($reservation->fresh()->collateral_status)->toBe('held')
        ->and($reservation->fresh()->gownRelease->release_date->toDateString())->toBe(today()->toDateString())
        ->and($gown->fresh()->status)->toBe('reserved');

    $this->actingAs($employee)
        ->get(route('employee.rentals'))
        ->assertOk()
        ->assertSee('Record Return')
        ->assertSee(route('employee.rentals.return', $reservation), false)
        ->assertSee('min="'.today()->toDateString().'"', false);

    $this->actingAs($employee)
        ->post(route('employee.rentals.return', $reservation), [
            'actual_return_date' => today()->toDateString(),
            'condition_after' => 'good',
        ])
        ->assertRedirect(route('employee.rentals'));

    expect($reservation->fresh()->status)->toBe('returned')
        ->and($reservation->fresh()->gownReturn->actual_return_date->toDateString())->toBe(today()->toDateString());
});

it('places archive under management and exposes landing links in the mobile menu', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $ownerHtml = $this->actingAs($owner)->get(route('owner.dashboard'))->assertOk()->getContent();
    preg_match('/<nav class="sb-side-links" data-nav-section="management">(.*?)<\\/nav>/s', $ownerHtml, $managementSection);
    preg_match('/<div class="sb-inventory-subnav">(.*?)<\\/div>/s', $ownerHtml, $inventorySection);

    expect($managementSection[1] ?? '')->toContain('>Archive</span>')
        ->and($inventorySection[1] ?? '')->not->toContain('Archive');

    auth()->logout();
    $this->get('/')
        ->assertOk()
        ->assertSee('aria-label="Toggle site navigation"', false)
        ->assertSee('id="sb-home-mobile-menu"', false)
        ->assertSee('href="#about"', false)
        ->assertSee('href="#contact"', false);
});
