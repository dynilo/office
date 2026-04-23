<?php

use App\Domain\Executions\Enums\ExecutionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ExecutionLog;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the execution monitor page with initial execution details', function (): void {
    $agent = Agent::factory()->create([
        'name' => 'Research Analyst',
    ]);
    $task = Task::factory()->for($agent)->create([
        'title' => 'Prepare competitor scan',
    ]);
    $execution = Execution::factory()->for($task)->for($agent)->create([
        'status' => ExecutionStatus::Running,
        'attempt' => 2,
        'retry_count' => 1,
        'max_retries' => 3,
        'failure_classification' => null,
    ]);

    ExecutionLog::factory()->for($execution)->create([
        'sequence' => 1,
        'level' => 'info',
        'message' => 'Execution started by worker.',
        'context' => [
            'phase' => 'start',
        ],
    ]);

    $response = $this->get('/admin/executions');

    $response->assertOk()
        ->assertSee('Execution monitor active')
        ->assertSee('Prepare competitor scan')
        ->assertSee('Research Analyst')
        ->assertSee('running')
        ->assertSee('attempt 2')
        ->assertSee('Execution details')
        ->assertSee('Execution log stream')
        ->assertSee('Execution started by worker.')
        ->assertSee('phase');
});

it('renders empty execution monitor states without runtime data', function (): void {
    $response = $this->get('/admin/executions');

    $response->assertOk()
        ->assertSee('Execution monitor active')
        ->assertSee('No executions exist yet')
        ->assertSee('Select an execution to inspect its state, retry counters, and log stream')
        ->assertSee('Refresh')
        ->assertSee('Start polling');
});

it('exposes execution api integration bootstrap on the monitor page', function (): void {
    $response = $this->get('/admin/executions');

    $response->assertOk()
        ->assertSee('window.OfficeAdmin', false)
        ->assertSee('executionMonitor', false)
        ->assertSee('initialExecutions', false)
        ->assertSee('refreshIntervalMs', false)
        ->assertSee('\/api\/admin\/executions', false)
        ->assertSee('execution-refresh')
        ->assertSee('execution-poll');
});
