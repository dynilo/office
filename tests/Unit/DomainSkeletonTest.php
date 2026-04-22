<?php

use App\Domain\Agents\Data\AgentIdentityData;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Data\ExecutionResultSummaryData;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Data\TaskPayloadSummaryData;
use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Support\Exceptions\EntityNotFoundException;
use App\Support\Exceptions\InvalidStateException;

it('hydrates agent identity data from arrays', function (): void {
    $data = AgentIdentityData::fromArray([
        'agent_id' => 'agent_support_v1',
        'name' => 'Support Agent',
        'version' => '1.0.0',
        'status' => 'active',
    ]);

    expect($data->agentId)->toBe('agent_support_v1')
        ->and($data->status)->toBe(AgentStatus::Active)
        ->and($data->toArray())->toBe([
            'agent_id' => 'agent_support_v1',
            'name' => 'Support Agent',
            'version' => '1.0.0',
            'status' => 'active',
        ]);
});

it('rejects invalid agent identity data', function (): void {
    AgentIdentityData::fromArray([
        'agent_id' => '',
        'name' => 'Support Agent',
        'version' => '1.0.0',
        'status' => 'active',
    ]);
})->throws(InvalidArgumentException::class, 'Agent ID cannot be empty.');

it('hydrates task payload summary data from arrays', function (): void {
    $data = TaskPayloadSummaryData::fromArray([
        'task_id' => 'task_123',
        'title' => 'Draft response',
        'status' => 'queued',
        'priority' => 'high',
        'input_count' => 3,
    ]);

    expect($data->status)->toBe(TaskStatus::Queued)
        ->and($data->priority)->toBe(TaskPriority::High)
        ->and($data->toArray()['input_count'])->toBe(3);
});

it('rejects negative task payload counts', function (): void {
    new TaskPayloadSummaryData(
        taskId: 'task_123',
        title: 'Draft response',
        status: TaskStatus::Pending,
        priority: TaskPriority::Normal,
        inputCount: -1,
    );
})->throws(InvalidArgumentException::class, 'Task input count cannot be negative.');

it('hydrates execution result summary data from arrays', function (): void {
    $data = ExecutionResultSummaryData::fromArray([
        'execution_id' => 'exec_456',
        'status' => 'succeeded',
        'output_reference' => 'outputs/exec_456.json',
        'error_message' => null,
    ]);

    expect($data->status)->toBe(ExecutionStatus::Succeeded)
        ->and($data->isSuccessful())->toBeTrue()
        ->and($data->toArray()['output_reference'])->toBe('outputs/exec_456.json');
});

it('rejects blank execution error messages when provided', function (): void {
    new ExecutionResultSummaryData(
        executionId: 'exec_456',
        status: ExecutionStatus::Failed,
        outputReference: null,
        errorMessage: ' ',
    );
})->throws(InvalidArgumentException::class, 'Execution error message cannot be blank when provided.');

it('exposes operational and terminal enum behavior', function (): void {
    expect(AgentStatus::Active->isOperational())->toBeTrue()
        ->and(AgentStatus::Archived->isOperational())->toBeFalse()
        ->and(TaskStatus::Completed->isTerminal())->toBeTrue()
        ->and(TaskStatus::Queued->isTerminal())->toBeFalse()
        ->and(ExecutionStatus::Failed->isTerminal())->toBeTrue()
        ->and(ExecutionStatus::Running->isTerminal())->toBeFalse()
        ->and(TaskPriority::Critical->weight())->toBeGreaterThan(TaskPriority::Normal->weight());
});

it('builds shared runtime exception messages', function (): void {
    $missing = EntityNotFoundException::for('Agent', 'agent_support_v1');
    $invalid = InvalidStateException::forTransition('Task', 'queued', 'pending');

    expect($missing->getMessage())->toBe('Agent [agent_support_v1] was not found.')
        ->and($invalid->getMessage())->toBe('Invalid state transition for Task from [queued] to [pending].');
});
