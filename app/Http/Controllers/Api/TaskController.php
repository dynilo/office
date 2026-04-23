<?php

namespace App\Http\Controllers\Api;

use App\Application\Audit\Data\AuditEventData;
use App\Application\Audit\Data\AuditSubjectData;
use App\Application\Audit\Services\AuditEventWriter;
use App\Application\Audit\Services\AuthenticatedAuditActorResolver;
use App\Application\Tasks\Actions\CreateTaskAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\TaskResource;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    public function __construct(
        private readonly AuditEventWriter $audit,
        private readonly AuthenticatedAuditActorResolver $actors,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $tasks = Task::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, CreateTaskAction $action): JsonResponse
    {
        $task = $action->execute($request->validated());

        $this->audit->write(new AuditEventData(
            eventName: 'task.created',
            subject: new AuditSubjectData('task', $task->id),
            actor: $this->actors->resolve($request->user()),
            source: 'api.task_intake',
            metadata: [
                'state' => $task->status->value,
                'priority' => $task->priority->value,
                'source' => $task->source,
                'requested_agent_role' => $task->requested_agent_role,
            ],
        ));

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Task $task): TaskResource
    {
        return new TaskResource($task);
    }
}
