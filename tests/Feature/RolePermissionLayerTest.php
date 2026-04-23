<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('assigns roles to users and resolves permissions through roles', function (): void {
    $user = User::factory()->create();
    $role = Role::query()->create([
        'name' => Role::ADMIN,
        'label' => 'Admin',
    ]);
    $permission = Permission::query()->create([
        'name' => Permission::MANAGE_AGENTS,
        'label' => 'Manage agents',
    ]);

    $role->permissions()->attach($permission);
    $user->assignRole($role);

    expect($user->hasRole(Role::ADMIN))->toBeTrue()
        ->and($user->hasPermission(Permission::MANAGE_AGENTS))->toBeTrue()
        ->and($role->users()->whereKey($user->id)->exists())->toBeTrue()
        ->and($permission->roles()->whereKey($role->id)->exists())->toBeTrue();
});

it('allows only configured operational roles into the admin shell', function (string $role): void {
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
})->with([
    Role::SUPER_ADMIN,
    Role::ADMIN,
    Role::OPERATOR,
    Role::OBSERVER,
]);

it('blocks authenticated users without an allowed admin role', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});

it('blocks authenticated users with roles outside the configured admin allow-list', function (): void {
    $user = User::factory()->create();
    $user->assignRole('external_reviewer');

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});
