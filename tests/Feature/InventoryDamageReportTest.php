<?php

use App\Models\Category;
use App\Models\DamageReport;
use App\Models\Gown;
use App\Models\GownPurchase;
use App\Models\MaintenanceRecord;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $category = Category::create(['name' => 'Damage test gowns']);
    $this->gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-DAMAGE-TEST-001',
        'name' => 'Damage test gown',
        'rental_price' => 1800,
        'security_deposit' => 0,
        'status' => 'available',
    ]);
});

it('shows the damage reporting action and form to the owner', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->get(route('owner.damages'))
        ->assertOk()
        ->assertSee('Report damage')
        ->assertSee('Damage description')
        ->assertSee('Date discovered')
        ->assertSee('Photo evidence')
        ->assertSee('Estimated repair cost')
        ->assertSee('sb-gown-picker', false)
        ->assertSee(route('owner.damages.gowns'), false)
        ->assertDontSee('Estimated repairs');
});

it('archives damage reports without deleting them and allows restoration', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    Storage::disk('public')->put('gowns/damage-test-gown.jpg', 'image data');
    $this->gown->update(['image' => 'gowns/damage-test-gown.jpg']);
    $damage = DamageReport::create([
        'gown_id' => $this->gown->id,
        'damage_type' => 'Loose seam',
        'description' => 'Needs stitching.',
        'severity' => 'minor',
        'discovered_at' => today(),
    ]);

    $this->actingAs($owner)
        ->get(route('owner.damages'))
        ->assertOk()
        ->assertSee('Archive')
        ->assertSee(route('owner.damages.archive.store', $damage), false);

    $this->get(route('owner.archive'))
        ->assertOk()
        ->assertSee(route('owner.damages.archive'), false)
        ->assertSee('View archived damage reports');

    $this->patch(route('owner.damages.archive.store', $damage))
        ->assertRedirect();

    expect($damage->fresh()->archived_at)->not->toBeNull();

    $this->get(route('owner.damages'))
        ->assertOk()
        ->assertDontSee('Needs stitching.');

    $this->get(route('owner.damages.archive'))
        ->assertOk()
        ->assertSee('Needs stitching.')
        ->assertSee(route('owner.damages.restore', $damage), false);

    $this->get(route('owner.archive'))
        ->assertOk()
        ->assertSee('Archived damage reports')
        ->assertSee('Needs stitching.')
        ->assertSee('sb-damage-archive-table', false)
        ->assertSee('data-record-details=', false)
        ->assertSee('data-record-image="' . asset('storage/gowns/damage-test-gown.jpg') . '"', false)
        ->assertSee('src="' . asset('storage/gowns/damage-test-gown.jpg') . '"', false)
        ->assertSee('alt="Damage test gown"', false)
        ->assertSee(route('owner.damages.restore', $damage), false);

    $this->patch(route('owner.damages.restore', $damage))
        ->assertRedirect();

    expect($damage->fresh()->archived_at)->toBeNull();

    $this->get(route('owner.damages'))
        ->assertOk()
        ->assertSee('Needs stitching.');
});

it('searches and paginates gown options for damage and purchase forms', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    foreach (range(1, 12) as $number) {
        Gown::create([
            'category_id' => $this->gown->category_id,
            'gown_code' => sprintf('GWN-OPTION-%03d', $number),
            'name' => "Picker gown {$number}",
            'rental_price' => 1800,
            'security_deposit' => 0,
            'status' => 'available',
        ]);
    }

    $this->actingAs($owner)
        ->getJson(route('owner.damages.gowns', ['page' => 1]))
        ->assertOk()
        ->assertJsonPath('total', 13)
        ->assertJsonPath('last_page', 3)
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('data.0.image_url', asset('images/image.webp'));

    $this->getJson(route('owner.damages.gowns', ['q' => 'GWN-OPTION-012']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.gown_code', 'GWN-OPTION-012');

    $this->get(route('owner.purchases'))
        ->assertOk()
        ->assertSee('sb-gown-picker', false)
        ->assertSee(route('owner.purchases.gowns'), false);

    $this->getJson(route('owner.purchases.gowns', ['q' => 'GWN-OPTION-012']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.gown_code', 'GWN-OPTION-012');
});

it('records damage details, private photo evidence, and an unavailable gown status', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $response = $this->actingAs($owner)->post(route('owner.damages.store'), [
        'gown_id' => $this->gown->id,
        'description' => 'Torn seam near the left sleeve.',
        'severity' => 'moderate',
        'discovered_at' => today()->toDateString(),
        'estimated_repair_cost' => '1250.00',
        'mark_unavailable' => '1',
        'photo' => UploadedFile::fake()->image('damage.jpg'),
    ]);

    $response->assertRedirect(route('owner.damages'));

    $damage = DamageReport::firstOrFail();
    expect($damage->gown_id)->toBe($this->gown->id)
        ->and($damage->description)->toBe('Torn seam near the left sleeve.')
        ->and($damage->severity)->toBe('moderate')
        ->and($damage->estimated_repair_cost)->toBe('1250.00')
        ->and($damage->final_repair_cost)->toBeNull()
        ->and($damage->photos)->toStartWith('damage-evidence/');

    expect($this->gown->fresh()->status)->toBe('damaged')
        ->and($this->gown->fresh()->condition)->toBe('damaged');

    Storage::disk('local')->assertExists($damage->photos);
    $this->get(route('owner.damages.photo', $damage))->assertOk();
});

it('adds gown status changes to the damage report log', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->patch(route('owner.gowns.update', $this->gown), [
            'category_id' => $this->gown->category_id,
            'name' => $this->gown->name,
            'size' => 'M',
            'color' => 'Ivory',
            'style' => null,
            'rental_price' => 1800,
            'security_deposit' => 0,
            'purchase_price' => 0,
            'description' => null,
            'measurements' => null,
            'condition' => 'good',
            'status' => 'damaged',
            'date_purchased' => null,
        ])
        ->assertRedirect(route('owner.gowns.index'));

    $damage = DamageReport::where('gown_id', $this->gown->id)->firstOrFail();
    expect($this->gown->fresh()->condition)->toBe('damaged')
        ->and($damage->damage_type)->toBe('Gown marked damaged');

    $this->get(route('owner.damages'))
        ->assertOk()
        ->assertSee($this->gown->name)
        ->assertSee('Gown marked damaged')
        ->assertSee('Gown status was marked damaged.');
});

it('adds damaged maintenance outcomes to the damage report log', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $record = MaintenanceRecord::create([
        'gown_id' => $this->gown->id,
        'processed_by' => $owner->id,
        'maintenance_type' => 'Repair',
        'description' => 'Check damaged stitching.',
        'cost' => 0,
        'maintenance_date' => today(),
        'status' => 'pending',
    ]);

    $this->actingAs($owner)
        ->post(route('owner.maintenance.complete', $record), [
            'condition' => 'damaged',
            'completion_notes' => 'Damage remains after inspection.',
        ])
        ->assertRedirect();

    expect($this->gown->fresh()->status)->toBe('damaged')
        ->and(DamageReport::where('gown_id', $this->gown->id)->count())->toBe(1);

    $this->get(route('owner.damages'))
        ->assertOk()
        ->assertSee($this->gown->name)
        ->assertSee('Gown marked damaged');
});

it('links a damage report to a relevant rental and rejects unrelated rentals', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $customer = \App\Models\Customer::create([
        'customer_code' => 'CUS-DAMAGE-TEST-001',
        'full_name' => 'Damage test customer',
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);
    $reservation = Reservation::create([
        'reservation_code' => 'RES-DAMAGE-TEST-001',
        'customer_id' => $customer->id,
        'pickup_date' => today(),
        'return_date' => today(),
        'rental_total' => 1800,
        'grand_total' => 1800,
        'balance' => 0,
        'status' => 'returned',
    ]);
    $reservation->items()->create([
        'gown_id' => $this->gown->id,
        'rental_price' => 1800,
        'security_deposit' => 0,
    ]);

    $this->actingAs($owner)->post(route('owner.damages.store'), [
        'gown_id' => $this->gown->id,
        'reservation_id' => $reservation->id,
        'description' => 'Loose beading.',
        'severity' => 'minor',
        'discovered_at' => today()->toDateString(),
    ])->assertRedirect(route('owner.damages'));

    expect(DamageReport::firstOrFail()->reservation_id)->toBe($reservation->id);

    $otherGown = Gown::create([
        'category_id' => $this->gown->category_id,
        'gown_code' => 'GWN-DAMAGE-TEST-002',
        'name' => 'Unrelated gown',
        'rental_price' => 1900,
        'security_deposit' => 0,
        'status' => 'available',
    ]);

    $this->post(route('owner.damages.store'), [
        'gown_id' => $otherGown->id,
        'reservation_id' => $reservation->id,
        'description' => 'Unrelated damage.',
        'severity' => 'minor',
        'discovered_at' => today()->toDateString(),
    ])->assertSessionHasErrors('reservation_id');
});

it('allows the owner to record the final repair cost later', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $damage = DamageReport::create([
        'gown_id' => $this->gown->id,
        'damage_type' => 'Manual damage report',
        'description' => 'Missing bead.',
        'severity' => 'minor',
        'discovered_at' => today(),
        'estimated_repair_cost' => 500,
    ]);

    $this->actingAs($owner)
        ->patch(route('owner.damages.final-cost', $damage), ['final_repair_cost' => '425.50'])
        ->assertRedirect();

    expect($damage->fresh()->final_repair_cost)->toBe('425.50')
        ->and($damage->fresh()->repair_cost)->toBe('425.50');
});

it('renders clickable detail rows for damage, maintenance, and purchase records', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    DamageReport::create([
        'gown_id' => $this->gown->id,
        'damage_type' => 'Bead repair',
        'description' => 'A bead is missing near the hem.',
        'severity' => 'minor',
        'discovered_at' => today(),
        'estimated_repair_cost' => 250,
    ]);
    MaintenanceRecord::create([
        'gown_id' => $this->gown->id,
        'processed_by' => $owner->id,
        'maintenance_type' => 'Cleaning',
        'description' => 'Cleaned after return.',
        'cost' => 100,
        'maintenance_date' => today(),
        'status' => 'completed',
    ]);
    GownPurchase::create([
        'gown_id' => $this->gown->id,
        'processed_by' => $owner->id,
        'amount' => 5000,
        'purchased_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('owner.damages'))
        ->assertOk()
        ->assertSee('class="sb-record-row"', false)
        ->assertSee('data-record-details=', false);

    $this->get(route('owner.maintenance'))
        ->assertOk()
        ->assertSee('class="sb-record-row"', false)
        ->assertSee('data-record-details=', false);

    $this->get(route('owner.purchases'))
        ->assertOk()
        ->assertSee('class="sb-record-row"', false)
        ->assertSee('data-record-details=', false);
});

it('opens pending maintenance completion in a centered dialog', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $record = MaintenanceRecord::create([
        'gown_id' => $this->gown->id,
        'processed_by' => $owner->id,
        'maintenance_type' => 'Repair',
        'description' => 'Repair the hem.',
        'cost' => 100,
        'maintenance_date' => today(),
        'status' => 'pending',
    ]);

    $this->actingAs($owner)
        ->get(route('owner.maintenance'))
        ->assertOk()
        ->assertSee('data-open-maintenance-dialog="#complete-maintenance-' . $record->id . '"', false)
        ->assertSee('class="sb-maintenance-complete-dialog"', false)
        ->assertSee('data-close-maintenance-dialog', false)
        ->assertSee('Close maintenance job');
});

it('prevents employees from creating damage reports or viewing private evidence', function () {
    $employee = User::factory()->create(['role' => 'employee']);

    $this->actingAs($employee)
        ->post(route('owner.damages.store'), [
            'gown_id' => $this->gown->id,
            'description' => 'Unauthorized report.',
            'severity' => 'minor',
            'discovered_at' => today()->toDateString(),
        ])
        ->assertForbidden();

    $damage = DamageReport::create([
        'gown_id' => $this->gown->id,
        'damage_type' => 'Test',
        'description' => 'Private photo test.',
        'severity' => 'minor',
        'photos' => 'missing-private-file.jpg',
    ]);

    $this->get(route('owner.damages.photo', $damage))->assertForbidden();
});
