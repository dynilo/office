<?php

use App\Application\Approvals\Enums\ApprovalStatus;
use App\Application\Approvals\Services\HumanApprovalGateService;
use App\Application\Executions\Services\ExecutionLifecycleService;
use App\Application\Organizations\Services\OrganizationSettingsService;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\ApprovalRequest;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Models\Organization;
use App\Support\Exceptions\InvalidStateException;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates approval request persistence for gated runtime actions', function (): void {
    expect(Schema::hasTable('approval_requests'))->toBeTrue()
        ->and(Schema::hasColumns('approval_requests', [
            'organization_id',
            'task_id',
            'agent_id',
            'action',
            'status',
            'reason',
            'requested_at',
            'decided_at',
        ]))->toBeTrue();
});

it('pauses execution start by creating a pending approval request', function (): void {
    $task = gatedTask();

    expect(fn () => app(ExecutionLifecycleService::class)->createPendingForAssignedTask(
        $task->id,
        'task-'.$task->id.'-attempt-1',
    ))->toThrow(InvalidStateException::class, 'Execution requires human approval before it can start.');

    $approval = ApprovalRequest::query()->sole();

    expect($approval->task_id)->toBe($task->id)
        ->and($approval->status)->toBe(ApprovalStatus::Pending)
        ->and($approval->organization_id)->toBe($task->organization_id)
        ->and(Execution::query()->count())->toBe(0);
});

it('does not create duplicate approval requests while execution remains paused', function (): void {
    $task = gatedTask();

    foreach ([1, 2] as $attempt) {
        try {
            app(ExecutionLifecycleService::class)->createPendingForAssignedTask(
                $task->id,
                'task-'.$task->id.'-attempt-'.$attempt,
            );
        } catch (InvalidStateException $exception) {
            expect($exception->getMessage())->toBe('Execution requires human approval before it can start.');
        }
    }

    expect(ApprovalRequest::query()->count())->toBe(1)
        ->and(Execution::query()->count())->toBe(0);
});

it('resumes execution after approval is granted', function (): void {
    $task = gatedTask();

    try {
        app(ExecutionLifecycleService::class)->createPendingForAssignedTask(
            $task->id,
            'task-'.$task->id.'-attempt-1',
        );
    } catch (InvalidStateException) {
        // expected pause
    }

    $approval = app(HumanApprovalGateService::class)->approve(
        ApprovalRequest::query()->sole()->id,
        decidedByType: 'user',
        decidedById: 'approver-1',
    );

    $execution = app(ExecutionLifecycleService::class)->createPendingForAssignedTask(
        $task->id,
        'task-'.$task->id.'-attempt-1',
    );

    expect($approval->status)->toBe(ApprovalStatus::Approved)
        ->and($execution->status->value)->toBe('pending')
        ->and(Execution::query()->count())->toBe(1);
});

it('blocks execution resume after rejection', function (): void {
    $task = gatedTask();

    try {
        app(ExecutionLifecycleService::class)->createPendingForAssignedTask(
            $task->id,
            'task-'.$task->id.'-attempt-1',
        );
    } catch (InvalidStateException) {
        // expected pause
    }

    $approval = app(HumanApprovalGateService::class)->reject(
        ApprovalRequest::query()->sole()->id,
        reason: 'Manual review denied.',
        decidedByType: 'user',
        decidedById: 'approver-1',
    );

    expect($approval->status)->toBe(ApprovalStatus::Rejected);

    expect(fn () => app(ExecutionLifecycleService::class)->createPendingForAssignedTask(
        $task->id,
        'task-'.$task->id.'-attempt-1',
    ))->toThrow(InvalidStateException::class, 'Execution is blocked by a rejected human approval request.');
});

function gatedTask(): Task
{
    $organization = Organization::factory()->create();
    app(OrganizationSettingsService::class)->store($organization, [
        'policy' => [
            'approvals_required' => true,
        ],
    ]);

    return app(TenantContext::class)->run($organization, function (): Task {
        $agent = Agent::factory()->create([
            'status' => AgentStatus::Active,
            'capabilities' => ['analysis', 'execute'],
        ]);

        return Task::factory()->for($agent)->create([
            'status' => TaskStatus::Queued,
        ]);
    });
}
