<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ExecutionLog;
use App\Infrastructure\Persistence\Eloquent\Models\ProviderUsageRecord;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Contracts\View\View;
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
            ->with('agent')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (Task $task): array => $this->serializeTask($task))
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
            ['key' => 'audit', 'label' => 'Audit', 'href' => route('admin.audit')],
        ]);
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
}
