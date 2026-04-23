<?php

namespace App\Http\Resources;

use App\Infrastructure\Persistence\Eloquent\Models\AgentCommunicationLog;
use App\Infrastructure\Persistence\Eloquent\Models\Artifact;
use App\Infrastructure\Persistence\Eloquent\Models\AuditEvent;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ExecutionLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'summary' => $this->summary,
            'description' => $this->description,
            'payload' => $this->payload ?? [],
            'priority' => $this->priority?->value,
            'source' => $this->source,
            'requested_agent_role' => $this->requested_agent_role,
            'state' => $this->status?->value,
            'agent_id' => $this->agent_id,
            'agent_name' => $this->whenLoaded('agent', fn () => $this->agent?->name),
            'due_at' => $this->due_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'executions' => $this->whenLoaded(
                'executions',
                fn (): array => $this->executions
                    ->sortByDesc('created_at')
                    ->map(fn (Execution $execution): array => $this->serializeExecution($execution))
                    ->values()
                    ->all(),
            ),
            'artifacts' => $this->whenLoaded(
                'artifacts',
                fn (): array => $this->artifacts
                    ->sortByDesc('created_at')
                    ->map(fn (Artifact $artifact): array => $this->serializeArtifact($artifact))
                    ->values()
                    ->all(),
            ),
            'audit_events' => $this->when(
                $this->relationLoaded('executions'),
                fn (): array => AuditEvent::query()
                    ->where('auditable_type', 'task')
                    ->where('auditable_id', $this->id)
                    ->orderByDesc('occurred_at')
                    ->orderByDesc('id')
                    ->limit(20)
                    ->get()
                    ->map(fn (AuditEvent $event): array => $this->serializeAuditEvent($event))
                    ->values()
                    ->all(),
            ),
            'communications' => $this->whenLoaded(
                'communicationLogs',
                fn (): array => $this->communicationLogs
                    ->sortBy('sent_at')
                    ->map(fn (AgentCommunicationLog $message): array => $this->serializeCommunication($message))
                    ->values()
                    ->all(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeExecution(Execution $execution): array
    {
        return [
            'id' => $execution->id,
            'task_id' => $execution->task_id,
            'agent_id' => $execution->agent_id,
            'agent_name' => $execution->agent?->name,
            'status' => $execution->status?->value,
            'attempt' => $execution->attempt,
            'retry_count' => $execution->retry_count,
            'max_retries' => $execution->max_retries,
            'failure_classification' => $execution->failure_classification,
            'error_message' => $execution->error_message,
            'output_payload' => $execution->output_payload ?? [],
            'provider_response' => $execution->provider_response ?? [],
            'started_at' => $execution->started_at?->toIso8601String(),
            'finished_at' => $execution->finished_at?->toIso8601String(),
            'created_at' => $execution->created_at?->toIso8601String(),
            'logs' => $execution->logs
                ->map(fn (ExecutionLog $log): array => [
                    'id' => $log->id,
                    'sequence' => $log->sequence,
                    'level' => $log->level,
                    'message' => $log->message,
                    'context' => $log->context ?? [],
                    'logged_at' => $log->logged_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeArtifact(Artifact $artifact): array
    {
        return [
            'id' => $artifact->id,
            'execution_id' => $artifact->execution_id,
            'kind' => $artifact->kind,
            'name' => $artifact->name,
            'content_text' => $artifact->content_text,
            'content_json' => $artifact->content_json ?? [],
            'file_metadata' => $artifact->file_metadata ?? [],
            'metadata' => $artifact->metadata ?? [],
            'created_at' => $artifact->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAuditEvent(AuditEvent $event): array
    {
        return [
            'id' => $event->id,
            'event_name' => $event->event_name,
            'actor_type' => $event->actor_type,
            'actor_id' => $event->actor_id,
            'source' => $event->source,
            'metadata' => $event->metadata ?? [],
            'occurred_at' => $event->occurred_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCommunication(AgentCommunicationLog $message): array
    {
        return [
            'id' => $message->id,
            'sender_agent_id' => $message->sender_agent_id,
            'sender_name' => $message->sender?->name,
            'recipient_agent_id' => $message->recipient_agent_id,
            'recipient_name' => $message->recipient?->name,
            'message_type' => $message->message_type,
            'subject' => $message->subject,
            'body' => $message->body,
            'metadata' => $message->metadata ?? [],
            'sent_at' => $message->sent_at?->toIso8601String(),
        ];
    }
}
