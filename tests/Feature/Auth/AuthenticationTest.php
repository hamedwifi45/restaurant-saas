<?php

use App\Models\Restaurant;
use App\Models\User;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('مطعمك')
        ->assertSee('data-test="login-button"', false)
        ->assertDontSee('<flux:', false);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticated();
});

test('authenticated customers visiting login or registration are redirected home', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('login'))
        ->assertRedirect(route('home'));

    $this->get(route('register'))
        ->assertRedirect(route('home'));
});

test('authenticated drivers visiting login are redirected to their restaurant panel', function () {
    $restaurant = Restaurant::factory()->create();
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);

    $this->actingAs($driver)
        ->get(route('login'))
        ->assertRedirect(route('driver.orders', ['slug' => $restaurant->slug]));
});

test('drivers are redirected to their restaurant orders after login', function () {
    $restaurant = Restaurant::factory()->create();
    $user = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('driver.orders', ['slug' => $restaurant->slug]));

    $this->assertAuthenticatedAs($user);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});
