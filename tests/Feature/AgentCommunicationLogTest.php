<?php

use App\Application\Communications\Data\AgentMessageData;
use App\Application\Communications\Services\AgentCommunicationQueryService;
use App\Application\Communications\Services\AgentCommunicationWriter;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentCommunicationLog;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists structured agent-to-agent messages', function (): void {
    $sender = Agent::factory()->create([
        'name' => 'Coordinator',
        'role' => 'coordinator',
    ]);
    $recipient = Agent::factory()->create([
        'name' => 'Research Analyst',
        'role' => 'research',
    ]);
    $task = Task::factory()->create([
        'agent_id' => $recipient->id,
        'title' => 'Research competitor pricing',
    ]);

    $message = app(AgentCommunicationWriter::class)->write(new AgentMessageData(
        senderAgentId: $sender->id,
        recipientAgentId: $recipient->id,
        messageType: 'handoff.request',
        body: 'Please investigate competitor pricing and return a short summary.',
        subject: 'Competitor pricing research',
        taskId: $task->id,
        metadata: [
            'priority' => 'high',
            'trace' => [
                'coordinator_task_id' => $task->parent_task_id,
            ],
        ],
    ));

    expect($message)->toBeInstanceOf(AgentCommunicationLog::class)
        ->and($message->sender_agent_id)->toBe($sender->id)
        ->and($message->recipient_agent_id)->toBe($recipient->id)
        ->and($message->task_id)->toBe($task->id)
        ->and($message->message_type)->toBe('handoff.request')
        ->and($message->subject)->toBe('Competitor pricing research')
        ->and($message->body)->toContain('competitor pricing')
        ->and($message->metadata['priority'])->toBe('high')
        ->and($message->sent_at)->not->toBeNull();

    expect($message->sender->is($sender))->toBeTrue()
        ->and($message->recipient->is($recipient))->toBeTrue()
        ->and($message->task->is($task))->toBeTrue();
});

it('queries communication history deterministically by agents and task', function (): void {
    $coordinator = Agent::factory()->create(['role' => 'coordinator']);
    $research = Agent::factory()->create(['role' => 'research']);
    $operations = Agent::factory()->create(['role' => 'operations']);
    $task = Task::factory()->create();

    $writer = app(AgentCommunicationWriter::class);

    $first = $writer->write(new AgentMessageData(
        senderAgentId: $coordinator->id,
        recipientAgentId: $research->id,
        messageType: 'coordination.note',
        body: 'Initial research handoff.',
        taskId: $task->id,
        sentAt: now()->subMinutes(2),
    ));
    $second = $writer->write(new AgentMessageData(
        senderAgentId: $research->id,
        recipientAgentId: $coordinator->id,
        messageType: 'status.update',
        body: 'Research is underway.',
        taskId: $task->id,
        sentAt: now()->subMinute(),
    ));
    $writer->write(new AgentMessageData(
        senderAgentId: $operations->id,
        recipientAgentId: $coordinator->id,
        messageType: 'status.update',
        body: 'Operations update for a different thread.',
        sentAt: now(),
    ));

    $query = app(AgentCommunicationQueryService::class);

    expect($query->betweenAgents($coordinator->id, $research->id)->pluck('id')->all())->toBe([
        $first->id,
        $second->id,
    ]);

    expect($query->forTask($task->id)->pluck('id')->all())->toBe([
        $first->id,
        $second->id,
    ]);

    expect($query->forAgent($coordinator->id))->toHaveCount(3);
});

it('validates message DTO requirements', function (): void {
    $agent = Agent::factory()->create();

    expect(fn () => new AgentMessageData(
        senderAgentId: $agent->id,
        recipientAgentId: $agent->id,
        messageType: 'coordination.note',
        body: 'Self message.',
    ))->toThrow(InvalidArgumentException::class, 'Agent message sender and recipient must be different agents.');

    expect(fn () => new AgentMessageData(
        senderAgentId: $agent->id,
        recipientAgentId: 'recipient-agent',
        messageType: '',
        body: 'Missing type.',
    ))->toThrow(InvalidArgumentException::class, 'Agent message type cannot be empty.');

    expect(fn () => new AgentMessageData(
        senderAgentId: $agent->id,
        recipientAgentId: 'recipient-agent',
        messageType: 'coordination.note',
        body: '',
    ))->toThrow(InvalidArgumentException::class, 'Agent message body cannot be empty.');
});
