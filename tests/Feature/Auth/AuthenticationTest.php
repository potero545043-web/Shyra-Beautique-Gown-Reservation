<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('login screen can be rendered', function () {
    encryptPasswordFields([]);
    $response = $this->get('/login');

    $response->assertOk()
        ->assertSee('login-password-public-key', false)
        ->assertDontSee('Continue with Google')
        ->assertDontSee('auth.google');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', encryptPasswordFields([
        'email' => $user->email,
        'password' => 'password',
    ]));

    $this->assertAuthenticated();
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    $response->assertRedirect(route('customer.dashboard', absolute: false));
});

test('plain password fields are rejected before authentication', function () {
    $this->post('/login', [
        'email' => 'owner@example.test',
        'password' => 'synthetic-test-password',
    ])->assertSessionHasErrors('password');
});

test('guests are sent to login and returned to their requested owner page after authentication', function () {
    $user = User::factory()->create(['role' => 'owner']);

    $this->get(route('owner.rentals'))
        ->assertRedirect(route('login'));

    $response = $this->post('/login', encryptPasswordFields([
        'email' => $user->email,
        'password' => 'password',
    ]));

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('owner.rentals'));
});

test('all account workspaces and their catalog links require authentication', function () {
    foreach ([
        '/owner/dashboard',
        '/owner/rentals',
        '/owner/catalog',
        '/owner/reservations',
        '/employee/dashboard',
        '/employee/rentals',
        '/employee/catalog',
        '/employee/reservations',
        '/customer/dashboard',
        '/customer/catalog',
        '/customer/reservations',
    ] as $path) {
        $this->get($path)->assertRedirect(route('login'));
    }
});

test('account routes reject an authenticated user with the wrong role', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($customer)->get('/owner/catalog')->assertForbidden();
    $this->actingAs($owner)->get('/customer/catalog')->assertForbidden();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', encryptPasswordFields([
        'email' => $user->email,
        'password' => 'wrong-password',
    ]));

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
