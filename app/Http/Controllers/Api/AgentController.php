<?php

namespace App\Http\Controllers\Api;

use App\Application\Agents\Actions\CreateAgentAction;
use App\Application\Agents\Actions\SetAgentActivationAction;
use App\Application\Agents\Actions\UpdateAgentAction;
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
        return new AgentResource($action->execute($agent, $request->validated()));
    }

    public function activate(Agent $agent, SetAgentActivationAction $action): AgentResource
    {
        return new AgentResource($action->activate($agent));
    }

    public function deactivate(Agent $agent, SetAgentActivationAction $action): AgentResource
    {
        return new AgentResource($action->deactivate($agent));
    }
}
