<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('redirects unauthenticated admin users to login', function (): void {
    $this->get('/admin')
        ->assertRedirect('/login');

    $this->get('/admin/tasks')
        ->assertRedirect('/login');
});

it('allows authenticated users to access the admin shell', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::OBSERVER);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Admin Shell');
});

it('renders the login form for guests and redirects authenticated users away from it', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Admin login')
        ->assertSee('Email')
        ->assertSee('Password');

    $this->actingAs(User::factory()->create())
        ->get('/login')
        ->assertRedirect('/admin');
});

it('logs users in and out with session authentication', function (): void {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => Hash::make('correct-password'),
    ]);
    $user->assignRole(Role::OBSERVER);

    $this->post('/login', [
        'email' => 'admin@example.com',
        'password' => 'correct-password',
    ])->assertRedirect('/admin');

    $this->assertAuthenticatedAs($user);

    $this->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
});

it('rejects invalid login credentials', function (): void {
    User::factory()->create([
        'email' => 'admin@example.com',
        'password' => Hash::make('correct-password'),
    ]);

    $this->from('/login')->post('/login', [
        'email' => 'admin@example.com',
        'password' => 'wrong-password',
    ])->assertRedirect('/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
