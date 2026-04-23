<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminAuditEventResource;
use App\Http\Resources\AdminExecutionResource;
use App\Http\Resources\AgentResource;
use App\Http\Resources\TaskResource;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AuditEvent;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminController extends Controller
{
    public function summary(): JsonResponse
    {
        return response()->json([
            'data' => [
                'agents' => [
                    'total' => Agent::query()->count(),
                    'active' => Agent::query()->where('status', 'active')->count(),
                    'inactive' => Agent::query()->where('status', 'inactive')->count(),
                ],
                'tasks' => [
                    'total' => Task::query()->count(),
                    'queued' => Task::query()->where('status', 'queued')->count(),
                    'in_progress' => Task::query()->where('status', 'in_progress')->count(),
                    'completed' => Task::query()->where('status', 'completed')->count(),
                    'failed' => Task::query()->where('status', 'failed')->count(),
                ],
                'executions' => [
                    'total' => Execution::query()->count(),
                    'pending' => Execution::query()->where('status', 'pending')->count(),
                    'running' => Execution::query()->where('status', 'running')->count(),
                    'succeeded' => Execution::query()->where('status', 'succeeded')->count(),
                    'failed' => Execution::query()->where('status', 'failed')->count(),
                ],
                'audit' => [
                    'total' => AuditEvent::query()->count(),
                    'latest_event_at' => AuditEvent::query()->max('occurred_at'),
                ],
            ],
        ]);
    }

    public function agents(Request $request): AnonymousResourceCollection
    {
        $query = Agent::query()->with('profile');

        $this->applyOptionalFilter($query, 'status', $request->query('status'));
        $this->applyOptionalFilter($query, 'role', $request->query('role'));

        $this->applySorting(
            $query,
            $request,
            ['name', 'status', 'role', 'created_at'],
            'name',
        );

        return AgentResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function tasks(Request $request): AnonymousResourceCollection
    {
        $query = Task::query()->with('agent');

        $this->applyOptionalFilter($query, 'status', $request->query('status'));
        $this->applyOptionalFilter($query, 'requested_agent_role', $request->query('requested_agent_role'));
        $this->applyOptionalFilter($query, 'agent_id', $request->query('agent_id'));

        $this->applySorting(
            $query,
            $request,
            ['created_at', 'due_at', 'priority', 'status', 'title'],
            'created_at',
            'desc',
        );

        return TaskResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function executions(Request $request): AnonymousResourceCollection
    {
        $query = Execution::query()->with(['task', 'agent']);

        $this->applyOptionalFilter($query, 'status', $request->query('status'));
        $this->applyOptionalFilter($query, 'task_id', $request->query('task_id'));
        $this->applyOptionalFilter($query, 'agent_id', $request->query('agent_id'));

        $this->applySorting(
            $query,
            $request,
            ['created_at', 'started_at', 'finished_at', 'status', 'attempt'],
            'created_at',
            'desc',
        );

        return AdminExecutionResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function auditEvents(Request $request): AnonymousResourceCollection
    {
        $query = AuditEvent::query();

        $this->applyOptionalFilter($query, 'event_name', $request->query('event_name'));
        $this->applyOptionalFilter($query, 'auditable_type', $request->query('auditable_type'));
        $this->applyOptionalFilter($query, 'actor_type', $request->query('actor_type'));
        $this->applyOptionalFilter($query, 'source', $request->query('source'));

        $this->applySorting(
            $query,
            $request,
            ['occurred_at', 'event_name', 'created_at'],
            'occurred_at',
            'desc',
        );

        return AdminAuditEventResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    private function perPage(Request $request): int
    {
        return max(1, min((int) $request->integer('per_page', 15), 100));
    }

    private function applyOptionalFilter(Builder $query, string $column, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $query->where($column, $value);
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function applySorting(
        Builder $query,
        Request $request,
        array $allowed,
        string $defaultSort,
        string $defaultDirection = 'asc',
    ): void {
        $sort = $request->query('sort', $defaultSort);
        $direction = strtolower((string) $request->query('direction', $defaultDirection)) === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, $allowed, true)) {
            $sort = $defaultSort;
        }

        $query->orderBy($sort, $direction);

        if ($sort !== 'id') {
            $query->orderBy('id');
        }
    }
}
