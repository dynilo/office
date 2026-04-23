<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps the health endpoint public', function (): void {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertJsonPath('status', 'ok');
});

it('rejects unauthenticated access to protected runtime and admin APIs', function (string $method, string $uri): void {
    $this->json($method, $uri)
        ->assertUnauthorized();
})->with([
    ['GET', '/api/agents'],
    ['POST', '/api/agents'],
    ['GET', '/api/tasks'],
    ['POST', '/api/tasks'],
    ['POST', '/api/documents/ingest'],
    ['GET', '/api/admin/summary'],
]);

it('allows authenticated session access to protected APIs', function (): void {
    $this->actingAs(User::factory()->create());

    $this->getJson('/api/agents')->assertOk();
    $this->getJson('/api/tasks')->assertOk();
});

it('requires an allowed admin role for admin api access', function (): void {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/admin/summary')
        ->assertForbidden();

    $user = User::factory()->create();
    $user->assignRole(Role::OBSERVER);

    $this->actingAs($user)
        ->getJson('/api/admin/summary')
        ->assertOk();
});
