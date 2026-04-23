<?php

use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the task queue page with initial task data and inspection details', function (): void {
    Task::factory()->create([
        'title' => 'Map competitor funding signals',
        'summary' => 'Research funding movements before the weekly brief.',
        'description' => 'Inspect recent market activity and summarize signal quality.',
        'status' => TaskStatus::Queued,
        'priority' => TaskPriority::High,
        'source' => 'admin',
        'requested_agent_role' => 'research',
        'payload' => [
            'request' => 'Find funding signals',
            'market' => 'enterprise AI',
        ],
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::OPERATOR);

    $response = $this->actingAs($user)->get('/admin/tasks');

    $response->assertOk()
        ->assertSee('Task queue active')
        ->assertSee('Map competitor funding signals')
        ->assertSee('Research funding movements before the weekly brief.')
        ->assertSee('queued')
        ->assertSee('high')
        ->assertSee('research')
        ->assertSee('Task details')
        ->assertSee('Payload JSON')
        ->assertSee('enterprise AI');
});

it('renders task create controls for draft and queued intake', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::OPERATOR);

    $response = $this->actingAs($user)->get('/admin/tasks');

    $response->assertOk()
        ->assertSee('Create task')
        ->assertSee('Title')
        ->assertSee('Summary')
        ->assertSee('Description')
        ->assertSee('Priority')
        ->assertSee('Initial state')
        ->assertSee('Queued')
        ->assertSee('Draft')
        ->assertSee('Requested agent role')
        ->assertSee('Payload JSON');
});

it('exposes task api integration bootstrap on the queue page', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::OPERATOR);

    $response = $this->actingAs($user)->get('/admin/tasks');

    $response->assertOk()
        ->assertSee('window.OfficeAdmin', false)
        ->assertSee('taskQueue', false)
        ->assertSee('initialTasks', false)
        ->assertSee('\/api\/admin\/tasks', false)
        ->assertSee('\/api\/tasks', false)
        ->assertSee('task-refresh')
        ->assertSee('task-create-form');
});
