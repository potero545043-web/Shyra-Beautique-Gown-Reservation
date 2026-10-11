<?php

use App\Models\Category;
use App\Models\Gown;
use App\Models\User;

beforeEach(function () {
    $category = Category::create(['name' => 'Formal gowns']);
    $this->gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-UIX-001',
        'name' => 'Lavender gown',
        'rental_price' => 2000,
        'security_deposit' => 0,
        'status' => 'available',
    ]);
});

it('renders the notifications bell in the top bar and drops it from the sidebar', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $html = $this->actingAs($owner)->get(route('owner.dashboard'))->getContent();

    expect($html)->toContain('sb-bell-btn')      // bell icon present
        ->toContain('sb-bell-panel')             // dropdown present
        ->toContain('sb-i-bell')                 // bell svg symbol used
        ->toContain('href="' . route('owner.gowns.index') . '"')
        ->toContain('href="' . route('owner.gowns.index', ['status' => 'available']) . '"')
        ->toContain('href="' . route('owner.reservations', ['status' => 'pending']) . '"')
        ->toContain('href="' . route('owner.rentals') . '"')
        ->toContain('href="' . route('owner.customers') . '"')
        ->toContain('href="' . route('owner.payments') . '"')
        ->toContain('href="' . route('owner.gowns.show', $this->gown) . '"');
});

it('shows the Archive module route for owners', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->get(route('owner.archive'))
        ->assertOk()
        ->assertSee('Archived')
        ->assertSee('Archive');
});

it('asks for confirmation before restoring a gown from the archive', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $this->gown->update(['archived_at' => now(), 'archived_status' => 'available']);

    $this->actingAs($owner)
        ->get(route('owner.archive'))
        ->assertOk()
        ->assertSee('Are you sure you want to restore this gown? It will return to active inventory.')
        ->assertSee('data-confirm-yes="Yes, restore"', false);

    $this->get(route('owner.gowns.show', $this->gown))
        ->assertOk()
        ->assertSee('Are you sure you want to restore this gown? It will return to active inventory.');
});

it('removes the archive button from the gown edit form', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $html = $this->actingAs($owner)->get(route('owner.gowns.edit', $this->gown))->getContent();

    expect($html)->not->toContain('Archive gown');
});

it('shows the archive action on the gown view page instead', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->get(route('owner.gowns.show', $this->gown))
        ->assertOk()
        ->assertSee('Archive gown')
        ->assertSee('sb-gown-detail-heading', false)
        ->assertDontSee('sb-gown-archive-banner', false)
        ->assertDontSee('INVENTORY ·');
});

it('opens gowns from their inventory rows and exposes edit and archive actions', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $html = $this->actingAs($owner)
        ->get(route('owner.gowns.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('data-href="' . route('owner.gowns.show', $this->gown) . '"')
        ->toContain(route('owner.gowns.edit', $this->gown))
        ->toContain(route('owner.gowns.archive', $this->gown))
        ->toContain('name="_token"')
        ->toContain('data-confirm-message="You can restore this gown later from the Archive module."')
        ->not->toContain('onsubmit="return confirm(')
        ->toContain('Archive')
        ->not->toContain('View archive')
        ->not->toContain('class="sb-action-view"');
});

it('archives gowns from the inventory without deleting them', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->post(route('owner.gowns.archive', $this->gown))
        ->assertRedirect(route('owner.gowns.index', ['archived' => 1]));

    expect($this->gown->fresh()->archived_at)->not->toBeNull();
});

it('marks search and filter forms for live auto-reflection', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->get(route('owner.gowns.index'))
        ->assertOk()
        ->assertSee('data-live-filter', false);

    $this->actingAs($owner)
        ->get(route('owner.reservations'))
        ->assertOk()
        ->assertSee('data-live-filter', false);
});

it('filters the gown index by a live search term server-side', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    Gown::create([
        'category_id' => $this->gown->category_id,
        'gown_code' => 'GWN-UIX-002',
        'name' => 'Emerald gown',
        'rental_price' => 1500,
        'security_deposit' => 0,
        'status' => 'available',
    ]);

    $this->actingAs($owner)
        ->get(route('owner.gowns.index', ['q' => 'lavender']))
        ->assertOk()
        ->assertSee('Lavender gown')
        ->assertDontSee('Emerald gown');
});

it('auto-fills the return date to the last rental day in both wizards', function () {
    // The auto-fill is done client-side, so assert the wiring that drives it:
    // both wizards must carry the JS that sets the return date to the max day.
    $owner = User::factory()->create(['role' => 'owner']);

    $staffHtml = $this->actingAs($owner)
        ->get(route('owner.catalog.reserve', $this->gown))
        ->getContent();

    expect($staffHtml)->toContain('dateLimit')
        ->toContain('returned.value = max');

    $customer = User::factory()->create(['role' => 'customer']);
    $customerHtml = $this->actingAs($customer)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->get(route('customer.reserve'))
        ->getContent();

    expect($customerHtml)->toContain('applyReturnWindow')
        ->toContain('ret.value = max');
});

it('shows the notification bell for customers too', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer)
        ->get(route('customer.catalog'))
        ->assertOk()
        ->assertSee('sb-bell-btn', false)
        ->assertDontSee('Notifications</span>', false);
});

it('does not show a Notifications link in the sidebar for any role', function () {
    foreach (['owner', 'employee', 'customer'] as $role) {
        $user = User::factory()->create(['role' => $role]);
        $route = match ($role) {
            'owner' => 'owner.dashboard',
            'employee' => 'employee.dashboard',
            default => 'customer.dashboard',
        };

        $html = $this->actingAs($user)->get(route($route))->getContent();

        // The sidebar link markup specifically must be gone; the bell dropdown
        // legitimately still links to the notifications page.
        expect($html)->not->toContain('sb-side-link sb-notification-active')
            ->not->toContain('>Notifications</span>');
    }
});

it('uses the same simple hero design for every dashboard role', function () {
    foreach ([
        'owner' => ['owner.dashboard', 'owner.gowns.create', 'OWNER OVERVIEW'],
        'employee' => ['employee.dashboard', 'employee.catalog', 'EMPLOYEE OVERVIEW'],
        'customer' => ['customer.dashboard', 'customer.catalog', 'ACCOUNT OVERVIEW'],
    ] as $role => [$dashboardRoute, $actionRoute, $eyebrow]) {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->get(route($dashboardRoute))->assertOk();

        $response->assertSee('sb-simple-overview-hero', false)
            ->assertSee('sb-customer-hero-copy', false)
            ->assertDontSee('sb-overview-hero-art', false)
            ->assertDontSee(asset('images/overview-gown-line.svg'), false)
            ->assertSee(route($actionRoute), false)
            ->assertSee($eyebrow);
    }
});
