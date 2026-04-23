<?php

namespace App\Http\Controllers\Api;

use App\Application\Agents\Actions\CreateAgentAction;
use App\Application\Agents\Actions\SetAgentActivationAction;
use App\Application\Agents\Actions\UpdateAgentAction;
use App\Application\Audit\Data\AuditEventData;
use App\Application\Audit\Data\AuditSubjectData;
use App\Application\Audit\Services\AuditEventWriter;
use App\Application\Audit\Services\AuthenticatedAuditActorResolver;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Http\Resources\AgentResource;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AgentController extends Controller
{
    public function __construct(
        private readonly AuditEventWriter $audit,
        private readonly AuthenticatedAuditActorResolver $actors,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $agents = Agent::query()
            ->with('profile')
            ->orderBy('name')
            ->get();

        return AgentResource::collection($agents);
    }

    public function store(StoreAgentRequest $request, CreateAgentAction $action): JsonResponse
    {
        $agent = $action->execute($request->validated());

        $this->audit->write(new AuditEventData(
            eventName: 'agent.created',
            subject: new AuditSubjectData('agent', $agent->id),
            actor: $this->actors->resolve($request->user()),
            source: 'api.agent_registry',
            metadata: [
                'code' => $agent->code,
                'role' => $agent->role,
                'status' => $agent->status->value,
            ],
        ));

        return (new AgentResource($agent))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Agent $agent): AgentResource
    {
        return new AgentResource($agent->load('profile'));
    }

    public function update(UpdateAgentRequest $request, Agent $agent, UpdateAgentAction $action): AgentResource
    {
        $before = $this->agentAuditSnapshot($agent->loadMissing('profile'));
        $updated = $action->execute($agent, $request->validated());

        $this->audit->write(new AuditEventData(
            eventName: 'agent.updated',
            subject: new AuditSubjectData('agent', $updated->id),
            actor: $this->actors->resolve($request->user()),
            source: 'api.agent_registry',
            metadata: [
                'before' => $before,
                'after' => $this->agentAuditSnapshot($updated),
            ],
        ));

        return new AgentResource($updated);
    }

    public function activate(Agent $agent, SetAgentActivationAction $action): AgentResource
    {
        $updated = $action->activate($agent);

        $this->audit->write(new AuditEventData(
            eventName: 'agent.activated',
            subject: new AuditSubjectData('agent', $updated->id),
            actor: $this->actors->resolve(request()->user()),
            source: 'api.agent_registry',
            metadata: [
                'status' => $updated->status->value,
            ],
        ));

        return new AgentResource($updated);
    }

    public function deactivate(Agent $agent, SetAgentActivationAction $action): AgentResource
    {
        $updated = $action->deactivate($agent);

        $this->audit->write(new AuditEventData(
            eventName: 'agent.deactivated',
            subject: new AuditSubjectData('agent', $updated->id),
            actor: $this->actors->resolve(request()->user()),
            source: 'api.agent_registry',
            metadata: [
                'status' => $updated->status->value,
            ],
        ));

        return new AgentResource($updated);
    }

    /**
     * @return array<string, mixed>
     */
    private function agentAuditSnapshot(Agent $agent): array
    {
        return [
            'code' => $agent->code,
            'name' => $agent->name,
            'role' => $agent->role,
            'status' => $agent->status->value,
            'capabilities' => $agent->capabilities,
            'model_preference' => $agent->profile?->model_preference,
        ];
    }
}
