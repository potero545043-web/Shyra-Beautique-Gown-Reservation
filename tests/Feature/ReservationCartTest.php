<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Gown;
use App\Models\Reservation;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ReservationCart;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->customerUser = User::factory()->create(['role' => 'customer']);
    $this->employeeUser = User::factory()->create(['role' => 'employee']);
    $this->ownerUser = User::factory()->create(['role' => 'owner']);
    Employee::create([
        'user_id' => $this->employeeUser->id,
        'employee_code' => 'EMP-CART-001',
        'full_name' => $this->employeeUser->name,
        'position' => 'Rental Staff',
        'status' => 'active',
    ]);

    $category = Category::create(['name' => 'Formal gowns']);
    $this->gownA = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-CART-A',
        'name' => 'Cart gown A',
        'rental_price' => 2000,
        'security_deposit' => 0,
        'status' => 'available',
    ]);
    $this->gownB = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-CART-B',
        'name' => 'Cart gown B',
        'rental_price' => 1500,
        'security_deposit' => 0,
        'status' => 'available',
    ]);

    SystemSetting::create(['setting_key' => 'late_fee_per_day', 'setting_value' => '150']);
});

it('keeps customer and staff carts in separate session keys', function () {
    expect(ReservationCart::forRole('customer')->ids())->toBe([])
        ->and(ReservationCart::forRole('employee')->ids())->toBe([])
        ->and(ReservationCart::forRole('owner')->ids())->toBe([])
        ->and(ReservationCart::keyFor('customer'))->not->toBe(ReservationCart::keyFor('employee'))
        ->and(ReservationCart::keyFor('owner'))->not->toBe(ReservationCart::keyFor('employee'));
});

it('adds a gown to the customer cart from the catalog and shows it on the cart page', function () {
    // Adding to the cart keeps the customer on the page they were on (catalog).
    $this->actingAs($this->customerUser)
        ->from(route('customer.catalog'))
        ->post(route('customer.cart.add', $this->gownA))
        ->assertRedirect(route('customer.catalog'));

    expect(ReservationCart::forRole('customer')->ids())->toBe([$this->gownA->id]);

    $this->actingAs($this->customerUser)
        ->get(route('customer.cart'))
        ->assertOk()
        ->assertSee('Cart gown A')
        ->assertSee($this->gownA->gown_code)
        ->assertSee('Reserve 1 gown', false);
});

it('shows cleaning gowns with a readable catalog status badge', function () {
    $this->gownA->update(['status' => 'for_cleaning']);

    $this->actingAs($this->customerUser)
        ->get(route('customer.catalog'))
        ->assertOk()
        ->assertSee('class="sb-product-badge is-for_cleaning"', false)
        ->assertSee('For cleaning');
});

it('shows damaged gowns with a readable catalog status badge', function () {
    $this->gownA->update(['status' => 'damaged']);

    $this->actingAs($this->customerUser)
        ->get(route('customer.catalog'))
        ->assertOk()
        ->assertSee('class="sb-product-badge is-damaged"', false)
        ->assertSee('Damaged');
});

it('keeps a customer cart after logout and login without sharing it with another account', function () {
    $this->actingAs($this->customerUser)
        ->post(route('customer.cart.add', $this->gownA))
        ->assertRedirect();

    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();

    $this->post('/login', encryptPasswordFields([
        'email' => $this->customerUser->email,
        'password' => 'password',
    ]))->assertRedirect(route('customer.dashboard', absolute: false));

    $this->get(route('customer.cart'))
        ->assertOk()
        ->assertSee('Cart gown A');

    $anotherCustomer = User::factory()->create(['role' => 'customer']);
    $this->actingAs($anotherCustomer);
    expect(ReservationCart::forRole('customer')->ids())->toBe([]);

    $this->actingAs($this->customerUser);
    expect(ReservationCart::forRole('customer')->ids())->toBe([$this->gownA->id]);
});

it('does not add the same customer gown twice', function () {
    $this->actingAs($this->customerUser)->post(route('customer.cart.add', $this->gownA));
    $this->actingAs($this->customerUser)->post(route('customer.cart.add', $this->gownA));

    expect(ReservationCart::forRole('customer')->ids())->toBe([$this->gownA->id]);
});

it('reserves only the selected gown from Reserve Now and preserves the bulk cart', function () {
    $this->actingAs($this->customerUser)
        ->post(route('customer.cart.add', $this->gownA));

    $this->actingAs($this->customerUser)
        ->get(route('customer.reserve.gown', $this->gownB))
        ->assertOk()
        ->assertSee('Review this gown')
        ->assertSee($this->gownB->name)
        ->assertSee('name="gown_ids[]" value="'.$this->gownB->id.'"', false)
        ->assertSee('name="reservation_source" value="single"', false);

    $this->actingAs($this->customerUser)
        ->post(route('customer.reserve.store'), [
            'gown_ids' => [$this->gownB->id],
            'reservation_source' => 'single',
            'contact_number' => '09171234567',
            'pickup_date' => today()->addDays(2)->toDateString(),
            'return_date' => today()->addDays(3)->toDateString(),
            'government_id' => UploadedFile::fake()->image('government-id.jpg'),
            'agreement_accepted' => '1',
            'payment_method' => 'cash',
            'payment_option' => 'downpayment',
            'payment_amount' => '600',
        ])
        ->assertRedirect();

    $reservation = Reservation::with('items')->firstOrFail();
    expect($reservation->items->pluck('gown_id')->all())->toBe([$this->gownB->id])
        ->and(ReservationCart::forRole('customer')->ids())->toBe([$this->gownA->id]);
});

it('removes and clears customer cart gowns', function () {
    $this->actingAs($this->customerUser)->post(route('customer.cart.add', $this->gownA));
    $this->actingAs($this->customerUser)->post(route('customer.cart.add', $this->gownB));
    expect(ReservationCart::forRole('customer')->ids())->toBe([$this->gownA->id, $this->gownB->id]);

    $this->actingAs($this->customerUser)
        ->delete(route('customer.cart.remove', $this->gownA))
        ->assertRedirect(route('customer.cart'));
    expect(ReservationCart::forRole('customer')->ids())->toBe([$this->gownB->id]);

    $this->actingAs($this->customerUser)
        ->delete(route('customer.cart.clear'))
        ->assertRedirect(route('customer.catalog'));
    expect(ReservationCart::forRole('customer')->ids())->toBe([]);
});

it('adds a gown to the staff cart and shows it on the in-store cart page', function () {
    // Staff stay on the collection after adding, and open the cart themselves.
    $this->actingAs($this->employeeUser)
        ->from(route('employee.catalog'))
        ->post(route('employee.cart.add', $this->gownA))
        ->assertRedirect(route('employee.catalog'));

    expect(ReservationCart::forRole('employee')->ids())->toBe([$this->gownA->id]);

    $this->actingAs($this->employeeUser)
        ->get(route('employee.cart'))
        ->assertOk()
        ->assertSee('Cart gown A')
        ->assertSee('Reserve 1 gown', false);
});

it('reserves all staff cart gowns as one in-store reservation', function () {
    Storage::fake('public');
    $this->actingAs($this->employeeUser)->withSession(['staff_reservation_cart' => [$this->gownA->id, $this->gownB->id]]);

    // The wizard seeded from the cart shows both gowns.
    $wizardHtml = $this->actingAs($this->employeeUser)
        ->withSession(['staff_reservation_cart' => [$this->gownA->id, $this->gownB->id]])
        ->get(route('employee.reserve'))
        ->assertOk()
        ->assertSee('Cart gown A')
        ->assertSee('Cart gown B')
        ->assertSee('name="government_id"', false)
        ->assertDontSee('Add another gown to this reservation')
        ->getContent();

    expect(strpos($wizardHtml, '<span class="sb-kicker">STEP 3</span>'))
        ->toBeLessThan(strpos($wizardHtml, 'Live photo of valid physical government ID'));

    $this->actingAs($this->employeeUser)
        ->withSession(['staff_reservation_cart' => [$this->gownA->id, $this->gownB->id]])
        ->post(route('employee.reservations.store'), [
            'customer_name' => 'Walk-in Guest',
            'contact_number' => '09171234567',
            'pickup_date' => today()->addDays(2)->toDateString(),
            'return_date' => today()->addDays(3)->toDateString(),
            'payment_amount' => '3500.00',
            'payment_option' => 'full',
            'payment_method' => 'cash',
            'agreement_accepted' => '1',
            'government_id' => UploadedFile::fake()->image('id.jpg'),
        ]);

    $reservation = Reservation::with(['items', 'payments'])->firstOrFail();
    expect($reservation->items)->toHaveCount(2)
        ->and((float) $reservation->rental_total)->toBe(3500.0)
        ->and($reservation->status)->toBe('pending')
        ->and((float) $reservation->amount_paid)->toBe(0.0)
        ->and((float) $reservation->balance)->toBe(3500.0)
        ->and($reservation->agreement_accepted_at)->not->toBeNull()
        ->and($reservation->agreement_accepted_ip)->toBe('127.0.0.1')
        ->and($reservation->collateral_status)->toBe('not_received')
        ->and($reservation->government_id_photo_path)->not->toBeNull()
        ->and($reservation->physical_id_photo_path)->toBeNull()
        ->and(Storage::disk('local')->exists($reservation->government_id_photo_path))->toBeTrue()
        ->and($reservation->payments)->toHaveCount(1)
        ->and($reservation->payments->first()->status)->toBe('pending')
        ->and((float) $reservation->payments->first()->amount)->toBe(3500.0)
        ->and($reservation->items->pluck('gown_id')->all())
        ->toContain($this->gownA->id, $this->gownB->id);

    // The cart is emptied after the booking is saved.
    expect(ReservationCart::forRole('employee')->ids())->toBe([]);
});

it('lets owners add gowns to a separate cart and open the bulk reservation flow', function () {
    $catalogHtml = $this->actingAs($this->ownerUser)
        ->get(route('owner.catalog'))
        ->assertOk()
        ->assertSee('My Cart')
        ->assertSee(route('owner.cart.add', $this->gownA))
        ->getContent();

    expect(substr_count($catalogHtml, '>My Cart</span>'))->toBe(1);

    $this->actingAs($this->ownerUser)
        ->get(route('owner.catalog.show', $this->gownA))
        ->assertOk()
        ->assertSee(route('owner.cart.add', $this->gownA));

    $this->actingAs($this->ownerUser)
        ->from(route('owner.catalog'))
        ->post(route('owner.cart.add', $this->gownA))
        ->assertRedirect(route('owner.catalog'));

    expect(ReservationCart::forRole('owner')->ids())->toBe([$this->gownA->id])
        ->and(ReservationCart::forRole('employee')->ids())->toBe([]);

    $this->actingAs($this->ownerUser)
        ->get(route('owner.catalog'))
        ->assertOk()
        ->assertSee('sb-worktop-cart-count', false)
        ->assertSee('href="'.route('owner.cart').'"', false);

    $this->actingAs($this->ownerUser)
        ->get(route('owner.cart'))
        ->assertOk()
        ->assertSee('Cart gown A')
        ->assertSee(route('owner.reserve'))
        ->assertSee('Reserve 1 gown', false);

    $ownerWizardHtml = $this->actingAs($this->ownerUser)
        ->get(route('owner.reserve'))
        ->assertOk()
        ->assertSee('Cart gown A')
        ->assertSee('name="government_id"', false)
        ->getContent();

    expect(strpos($ownerWizardHtml, '<span class="sb-kicker">STEP 3</span>'))
        ->toBeLessThan(strpos($ownerWizardHtml, 'Live photo of valid physical government ID'));

    $this->actingAs($this->ownerUser)
        ->withSession(['owner_reservation_cart' => [$this->gownA->id]])
        ->post(route('owner.reservations.store'), [
            'customer_name' => 'Owner Walk-in',
            'contact_number' => '09171234567',
            'pickup_date' => today()->addDays(2)->toDateString(),
            'return_date' => today()->addDays(3)->toDateString(),
            'payment_option' => 'downpayment',
            'payment_amount' => '600.00',
            'payment_method' => 'cash',
            'agreement_accepted' => '1',
            'government_id' => UploadedFile::fake()->image('owner-id.jpg'),
        ]);

    $ownerReservation = Reservation::with('payments')->firstOrFail();
    expect($ownerReservation->status)->toBe('pending')
        ->and((float) $ownerReservation->amount_paid)->toBe(0.0)
        ->and((float) $ownerReservation->balance)->toBe(2000.0)
        ->and($ownerReservation->collateral_status)->toBe('not_received')
        ->and($ownerReservation->government_id_photo_path)->not->toBeNull()
        ->and($ownerReservation->physical_id_photo_path)->toBeNull()
        ->and($ownerReservation->payments->first()->status)->toBe('pending')
        ->and($ownerReservation->payments->first()->payment_type)->toBe('downpayment')
        ->and((float) $ownerReservation->payments->first()->amount)->toBe(600.0);
});

it('reserves only the selected gown from the staff Reserve Now page and preserves the cart', function () {
    $this->actingAs($this->ownerUser)
        ->post(route('owner.cart.add', $this->gownA));

    $this->actingAs($this->ownerUser)
        ->get(route('owner.catalog.reserve', $this->gownB))
        ->assertOk()
        ->assertSee('Reserve <em>1</em> gown', false)
        ->assertSee($this->gownB->name)
        ->assertSee('name="gown_ids[]" value="'.$this->gownB->id.'"', false)
        ->assertDontSee('name="gown_ids[]" value="'.$this->gownA->id.'"', false)
        ->assertSee('name="reservation_source" value="single"', false);

    $this->actingAs($this->ownerUser)
        ->post(route('owner.reservations.store'), [
            'reservation_source' => 'single',
            'gown_ids' => [$this->gownB->id],
            'customer_name' => 'Single Gown Customer',
            'contact_number' => '09171234567',
            'pickup_date' => today()->addDays(2)->toDateString(),
            'return_date' => today()->addDays(3)->toDateString(),
            'payment_option' => 'downpayment',
            'payment_amount' => '600.00',
            'payment_method' => 'cash',
            'agreement_accepted' => '1',
            'government_id' => UploadedFile::fake()->image('single-id.jpg'),
        ])->assertRedirect();

    $singleReservation = Reservation::with('items')->firstOrFail();
    expect($singleReservation->items->pluck('gown_id')->all())->toBe([$this->gownB->id])
        ->and(ReservationCart::forRole('owner')->ids())->toBe([$this->gownA->id]);
});

it('checks date availability for every gown in owner and employee reservations', function () {
    $existingCustomer = Customer::create([
        'customer_code' => 'CUS-CART-CONFLICT',
        'full_name' => 'Existing renter',
        'contact_number' => '09170000000',
        'status' => 'active',
    ]);
    $existingReservation = Reservation::create([
        'reservation_code' => 'GR-CART-CONFLICT',
        'customer_id' => $existingCustomer->id,
        'pickup_date' => today()->addDays(2)->toDateString(),
        'return_date' => today()->addDays(3)->toDateString(),
        'rental_total' => 2000,
        'grand_total' => 2000,
        'balance' => 2000,
        'status' => 'confirmed',
    ]);
    $existingReservation->items()->create([
        'gown_id' => $this->gownA->id,
        'rental_price' => 2000,
        'quantity' => 1,
    ]);

    $query = [
        'pickup_date' => today()->addDays(2)->toDateString(),
        'return_date' => today()->addDays(3)->toDateString(),
        'gown_ids' => [$this->gownA->id, $this->gownB->id],
    ];

    $this->actingAs($this->ownerUser)
        ->getJson(route('owner.reservations.availability', $query))
        ->assertOk()
        ->assertJsonPath('available', false)
        ->assertJsonPath('conflicts.'.$this->gownA->id.'.from', today()->addDays(2)->toDateString());

    $this->actingAs($this->employeeUser)
        ->getJson(route('employee.reservations.availability', $query))
        ->assertOk()
        ->assertJsonPath('available', false);

    $this->actingAs($this->ownerUser)
        ->getJson(route('owner.reservations.availability', [
            'pickup_date' => today()->addDays(4)->toDateString(),
            'return_date' => today()->addDays(4)->toDateString(),
            'gown_ids' => [$this->gownA->id],
        ]))
        ->assertOk()
        ->assertJsonPath('available', false)
        ->assertJsonPath('conflicts.'.$this->gownA->id.'.through', today()->addDays(4)->toDateString());

    $this->actingAs($this->employeeUser)
        ->getJson(route('employee.reservations.availability', [
            'pickup_date' => today()->addDays(5)->toDateString(),
            'return_date' => today()->addDays(5)->toDateString(),
            'gown_ids' => [$this->gownA->id],
        ]))
        ->assertOk()
        ->assertJsonPath('available', true);
});
