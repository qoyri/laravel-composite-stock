<?php

declare(strict_types=1);

use App\Models\User;

it('shows the login form', function () {
    $this->get(route('admin.login'))->assertOk()->assertSee('Se connecter');
});

it('logs a back-office user in and sends them to the dashboard', function () {
    $user = User::factory()->create(['email' => 'staff@example.ch']);

    $this->post(route('admin.login.store'), ['email' => 'staff@example.ch', 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('returns to the page that was asked for before logging in', function () {
    User::factory()->create(['email' => 'staff@example.ch']);

    $this->get(route('admin.orders.index'))->assertRedirect(route('admin.login'));
    $this->post(route('admin.login.store'), ['email' => 'staff@example.ch', 'password' => 'password'])
        ->assertRedirect(route('admin.orders.index'));
});

it('rejects wrong credentials', function () {
    User::factory()->create(['email' => 'staff@example.ch']);

    $this->post(route('admin.login.store'), ['email' => 'staff@example.ch', 'password' => 'nope'])
        ->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);

    $this->assertGuest();
});

it('locks the account out after five failed attempts', function () {
    User::factory()->create(['email' => 'staff@example.ch']);

    foreach (range(1, 5) as $_) {
        $this->post(route('admin.login.store'), ['email' => 'staff@example.ch', 'password' => 'nope']);
    }

    $this->post(route('admin.login.store'), ['email' => 'staff@example.ch', 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('logs out', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));

    $this->assertGuest();
});

it('sends an authenticated user away from the login page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.login'))
        ->assertRedirect(route('admin.dashboard'));
});
