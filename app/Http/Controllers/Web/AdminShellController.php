<?php

namespace App\Http\Controllers\Web;

use App\Application\Communications\Services\AgentCommunicationQueryService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentCommunicationLog;
use App\Infrastructure\Persistence\Eloquent\Models\Artifact;
use App\Infrastructure\Persistence\Eloquent\Models\AuditEvent;
use App\Infrastructure\Persistence\Eloquent\Models\Document;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ExecutionLog;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;
use App\Infrastructure\Persistence\Eloquent\Models\ProviderUsageRecord;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminShellController extends Controller
{
    public function dashboard(): View
    {
        $summary = $this->dashboardSummary();
        $recentTasks = Task::query()
            ->with('agent')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (Task $task): array => $this->serializeTask($task))
            ->values()
            ->all();
        $recentExecutions = Execution::query()
            ->with(['agent', 'task', 'logs' => fn ($query) => $query->orderBy('sequence')])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (Execution $execution): array => $this->serializeExecution($execution))
            ->values()
            ->all();

        return $this->page(
            page: 'dashboard',
            title: 'Dashboard',
            view: 'admin.dashboard',
            bootstrap: [
                'initialSummary' => $summary,
                'recentTasks' => $recentTasks,
                'recentExecutions' => $recentExecutions,
                'dashboardMetrics' => [
                    'summary' => route('api.admin.summary'),
                    'tasks' => route('api.admin.tasks'),
                    'executions' => route('api.admin.executions'),
                    'refreshIntervalMs' => 30000,
                ],
            ],
            data: [
                'summary' => $summary,
                'recentTasks' => $recentTasks,
                'recentExecutions' => $recentExecutions,
            ],
        );
    }

    public function agents(): View
    {
        $agents = Agent::query()
            ->with('profile')
            ->orderBy('name')
            ->get()
            ->map(fn (Agent $agent): array => $this->serializeAgent($agent))
            ->values()
            ->all();

        return $this->page(
            page: 'agents',
            title: 'Agents',
            view: 'admin.agents',
            bootstrap: [
                'initialAgents' => $agents,
                'agentManagement' => [
                    'list' => route('api.admin.agents'),
                    'create' => url('/api/agents'),
                    'show' => url('/api/agents'),
                    'update' => url('/api/agents'),
                    'activate' => url('/api/agents'),
                    'deactivate' => url('/api/agents'),
                ],
            ],
            data: [
                'agents' => $agents,
            ],
        );
    }

    public function tasks(): View
    {
        $tasks = Task::query()
            ->with([
                'agent',
                'executions.agent',
                'executions.logs' => fn ($query) => $query->orderBy('sequence'),
                'artifacts',
                'communicationLogs.sender',
                'communicationLogs.recipient',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (Task $task): array => $this->serializeTaskDetail($task))
            ->values()
            ->all();

        return $this->page(
            page: 'tasks',
            title: 'Tasks',
            view: 'admin.tasks',
            bootstrap: [
                'initialTasks' => $tasks,
                'taskQueue' => [
                    'list' => route('api.admin.tasks'),
                    'create' => url('/api/tasks'),
                    'show' => url('/api/tasks'),
                ],
            ],
            data: [
                'tasks' => $tasks,
            ],
        );
    }

    public function executions(): View
    {
        $executions = Execution::query()
            ->with(['agent', 'task', 'logs' => fn ($query) => $query->orderBy('sequence')])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (Execution $execution): array => $this->serializeExecution($execution))
            ->values()
            ->all();

        return $this->page(
            page: 'executions',
            title: 'Executions',
            view: 'admin.executions',
            bootstrap: [
                'initialExecutions' => $executions,
                'executionMonitor' => [
                    'list' => route('api.admin.executions'),
                    'refreshIntervalMs' => 15000,
                ],
            ],
            data: [
                'executions' => $executions,
            ],
        );
    }

    public function documents(): View
    {
        $documents = Document::query()
            ->with(['knowledgeItems' => fn ($query) => $query->orderBy('title')])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (Document $document): array => $this->serializeDocument($document))
            ->values()
            ->all();

        return $this->page(
            page: 'documents',
            title: 'Documents',
            view: 'admin.documents',
            bootstrap: [
                'initialDocuments' => $documents,
                'documentKnowledge' => [
                    'ingest' => url('/api/documents/ingest'),
                    'extractKnowledge' => url('/api/documents'),
                ],
            ],
            data: [
                'documents' => $documents,
            ],
        );
    }

    public function conversations(Request $request, AgentCommunicationQueryService $communications): View
    {
        $filters = [
            'task_id' => $request->query('task_id'),
            'agent_id' => $request->query('agent_id'),
            'first_agent_id' => $request->query('first_agent_id'),
            'second_agent_id' => $request->query('second_agent_id'),
        ];
        $messages = $this->conversationMessages($filters, $communications)
            ->map(fn (AgentCommunicationLog $message): array => $this->serializeCommunication($message))
            ->values()
            ->all();
        $agents = Agent::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Agent $agent): array => [
                'id' => $agent->id,
                'name' => $agent->name,
                'role' => $agent->role,
            ])
            ->values()
            ->all();
        $tasks = Task::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (Task $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'state' => $task->status?->value,
            ])
            ->values()
            ->all();

        return $this->page(
            page: 'conversations',
            title: 'Conversations',
            view: 'admin.conversations',
            bootstrap: [
                'initialMessages' => $messages,
                'conversationFilters' => array_filter($filters),
                'conversationOptions' => [
                    'agents' => $agents,
                    'tasks' => $tasks,
                ],
            ],
            data: [
                'messages' => $messages,
                'agents' => $agents,
                'tasks' => $tasks,
                'filters' => $filters,
            ],
        );
    }

    public function audit(): View
    {
        return $this->page('audit', 'Audit');
    }

    private function page(
        string $page,
        string $title,
        string $view = 'admin.page',
        array $bootstrap = [],
        array $data = [],
    ): View {
        return view($view, [
            'page' => $page,
            'pageTitle' => $title,
            'navigation' => $this->navigation(),
            'bootstrap' => [
                'app' => config('app.name'),
                'page' => $page,
                'title' => $title,
                'api' => [
                    'summary' => route('api.admin.summary'),
                    'agents' => route('api.admin.agents'),
                    'tasks' => route('api.admin.tasks'),
                    'executions' => route('api.admin.executions'),
                    'auditEvents' => route('api.admin.audit-events'),
                ],
                'realtime' => [
                    'enabled' => config('broadcasting.default') !== 'null',
                    'channel' => 'runtime',
                    'events' => [
                        'taskStatusChanged' => 'task.status.changed',
                        'executionCreated' => 'execution.created',
                        'executionStatusChanged' => 'execution.status.changed',
                    ],
                ],
                'navigation' => $this->navigation()->values()->all(),
                ...$bootstrap,
            ],
            ...$data,
        ]);
    }

    /**
     * @return Collection<int, array{key: string, label: string, href: string}>
     */
    private function navigation(): Collection
    {
        return collect([
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => route('admin.dashboard')],
            ['key' => 'agents', 'label' => 'Agents', 'href' => route('admin.agents')],
            ['key' => 'tasks', 'label' => 'Tasks', 'href' => route('admin.tasks')],
            ['key' => 'executions', 'label' => 'Executions', 'href' => route('admin.executions')],
            ['key' => 'documents', 'label' => 'Documents', 'href' => route('admin.documents')],
            ['key' => 'conversations', 'label' => 'Conversations', 'href' => route('admin.conversations')],
            ['key' => 'audit', 'label' => 'Audit', 'href' => route('admin.audit')],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AgentCommunicationLog>
     */
    private function conversationMessages(array $filters, AgentCommunicationQueryService $communications): Collection
    {
        $taskId = is_string($filters['task_id'] ?? null) ? $filters['task_id'] : null;
        $agentId = is_string($filters['agent_id'] ?? null) ? $filters['agent_id'] : null;
        $firstAgentId = is_string($filters['first_agent_id'] ?? null) ? $filters['first_agent_id'] : null;
        $secondAgentId = is_string($filters['second_agent_id'] ?? null) ? $filters['second_agent_id'] : null;

        if ($taskId !== null && $taskId !== '') {
            return $communications->forTask($taskId);
        }

        if ($firstAgentId !== null && $firstAgentId !== '' && $secondAgentId !== null && $secondAgentId !== '') {
            return $communications->betweenAgents($firstAgentId, $secondAgentId);
        }

        if ($agentId !== null && $agentId !== '') {
            return $communications->forAgent($agentId);
        }

        return AgentCommunicationLog::query()
            ->with(['sender', 'recipient', 'task'])
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardSummary(): array
    {
        $totalTasks = Task::query()->count();
        $completedTasks = Task::query()->where('status', 'completed')->count();
        $totalExecutions = Execution::query()->count();
        $succeededExecutions = Execution::query()->where('status', 'succeeded')->count();

        return [
            'agents' => [
                'total' => Agent::query()->count(),
                'active' => Agent::query()->where('status', 'active')->count(),
                'inactive' => Agent::query()->where('status', 'inactive')->count(),
            ],
            'tasks' => [
                'total' => $totalTasks,
                'draft' => Task::query()->where('status', 'draft')->count(),
                'queued' => Task::query()->where('status', 'queued')->count(),
                'in_progress' => Task::query()->where('status', 'in_progress')->count(),
                'completed' => $completedTasks,
                'failed' => Task::query()->where('status', 'failed')->count(),
                'completion_rate' => $totalTasks === 0 ? 0 : round(($completedTasks / $totalTasks) * 100, 1),
            ],
            'executions' => [
                'total' => $totalExecutions,
                'pending' => Execution::query()->where('status', 'pending')->count(),
                'running' => Execution::query()->where('status', 'running')->count(),
                'succeeded' => $succeededExecutions,
                'failed' => Execution::query()->where('status', 'failed')->count(),
                'success_rate' => $totalExecutions === 0 ? 0 : round(($succeededExecutions / $totalExecutions) * 100, 1),
            ],
            'costs' => [
                'total_tokens' => (int) ProviderUsageRecord::query()->sum('total_tokens'),
                'estimated_cost_micros' => (int) ProviderUsageRecord::query()->sum('estimated_cost_micros'),
                'currency' => (string) config('costs.currency', 'USD'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAgent(Agent $agent): array
    {
        return [
            'id' => $agent->id,
            'name' => $agent->name,
            'code' => $agent->code,
            'role' => $agent->role,
            'capabilities' => $agent->capabilities ?? [],
            'model_preference' => $agent->profile?->model_preference,
            'temperature_policy' => $agent->profile?->temperature_policy,
            'active' => $agent->status?->isOperational() ?? false,
            'status' => $agent->status?->value,
            'profile_id' => $agent->profile?->id,
            'created_at' => $agent->created_at?->toIso8601String(),
            'updated_at' => $agent->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'agent_id' => $task->agent_id,
            'agent_name' => $task->agent?->name,
            'title' => $task->title,
            'summary' => $task->summary,
            'description' => $task->description,
            'payload' => $task->payload ?? [],
            'priority' => $task->priority?->value,
            'source' => $task->source,
            'requested_agent_role' => $task->requested_agent_role,
            'state' => $task->status?->value,
            'due_at' => $task->due_at?->toIso8601String(),
            'submitted_at' => $task->submitted_at?->toIso8601String(),
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTaskDetail(Task $task): array
    {
        return [
            ...$this->serializeTask($task),
            'executions' => $task->executions
                ->sortByDesc('created_at')
                ->map(fn (Execution $execution): array => $this->serializeExecution($execution))
                ->values()
                ->all(),
            'artifacts' => $task->artifacts
                ->sortByDesc('created_at')
                ->map(fn (Artifact $artifact): array => $this->serializeArtifact($artifact))
                ->values()
                ->all(),
            'audit_events' => AuditEvent::query()
                ->where('auditable_type', 'task')
                ->where('auditable_id', $task->id)
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(fn (AuditEvent $event): array => $this->serializeAuditEvent($event))
                ->values()
                ->all(),
            'communications' => $task->communicationLogs
                ->sortBy('sent_at')
                ->map(fn (AgentCommunicationLog $message): array => $this->serializeCommunication($message))
                ->values()
                ->all(),
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
            'task_title' => $execution->task?->title,
            'agent_id' => $execution->agent_id,
            'agent_name' => $execution->agent?->name,
            'status' => $execution->status?->value,
            'attempt' => $execution->attempt,
            'retry_count' => $execution->retry_count,
            'max_retries' => $execution->max_retries,
            'failure_classification' => $execution->failure_classification,
            'error_message' => $execution->error_message,
            'input_snapshot' => $execution->input_snapshot ?? [],
            'output_payload' => $execution->output_payload ?? [],
            'provider_response' => $execution->provider_response ?? [],
            'started_at' => $execution->started_at?->toIso8601String(),
            'finished_at' => $execution->finished_at?->toIso8601String(),
            'next_retry_at' => $execution->next_retry_at?->toIso8601String(),
            'created_at' => $execution->created_at?->toIso8601String(),
            'updated_at' => $execution->updated_at?->toIso8601String(),
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
            'task_id' => $artifact->task_id,
            'execution_id' => $artifact->execution_id,
            'kind' => $artifact->kind,
            'name' => $artifact->name,
            'content_text' => $artifact->content_text,
            'content_json' => $artifact->content_json ?? [],
            'file_metadata' => $artifact->file_metadata ?? [],
            'metadata' => $artifact->metadata ?? [],
            'created_at' => $artifact->created_at?->toIso8601String(),
            'updated_at' => $artifact->updated_at?->toIso8601String(),
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
            'auditable_type' => $event->auditable_type,
            'auditable_id' => $event->auditable_id,
            'actor_type' => $event->actor_type,
            'actor_id' => $event->actor_id,
            'source' => $event->source,
            'metadata' => $event->metadata ?? [],
            'occurred_at' => $event->occurred_at?->toIso8601String(),
            'created_at' => $event->created_at?->toIso8601String(),
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
            'task_id' => $message->task_id,
            'task_title' => $message->task?->title,
            'message_type' => $message->message_type,
            'subject' => $message->subject,
            'body' => $message->body,
            'metadata' => $message->metadata ?? [],
            'sent_at' => $message->sent_at?->toIso8601String(),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDocument(Document $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'mime_type' => $document->mime_type,
            'storage_disk' => $document->storage_disk,
            'storage_path' => $document->storage_path,
            'checksum' => $document->checksum,
            'size_bytes' => $document->size_bytes,
            'raw_text' => $document->raw_text,
            'metadata' => $document->metadata ?? [],
            'ingested_at' => $document->ingested_at?->toIso8601String(),
            'text_extracted_at' => $document->text_extracted_at?->toIso8601String(),
            'created_at' => $document->created_at?->toIso8601String(),
            'updated_at' => $document->updated_at?->toIso8601String(),
            'knowledge_items' => $document->knowledgeItems
                ->map(fn (KnowledgeItem $item): array => [
                    'id' => $item->id,
                    'document_id' => $item->document_id,
                    'title' => $item->title,
                    'content' => $item->content,
                    'content_hash' => $item->content_hash,
                    'metadata' => $item->metadata ?? [],
                    'indexed_at' => $item->indexed_at?->toIso8601String(),
                    'created_at' => $item->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }
}
