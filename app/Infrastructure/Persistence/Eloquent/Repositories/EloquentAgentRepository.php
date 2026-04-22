<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Agents\Contracts\AgentRepository;
use App\Domain\Agents\Data\AgentIdentityData;
use App\Domain\Agents\Enums\AgentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use Illuminate\Support\Collection;

final class EloquentAgentRepository implements AgentRepository
{
    public function findIdentity(string $agentId): ?AgentIdentityData
    {
        $agent = Agent::query()->find($agentId);

        if ($agent === null) {
            return null;
        }

        return AgentIdentityData::fromArray([
            'agent_id' => $agent->id,
            'name' => $agent->name,
            'version' => $agent->version,
            'status' => $agent->status->value,
        ]);
    }

    public function exists(string $agentId): bool
    {
        return Agent::query()->whereKey($agentId)->exists();
    }

    public function findActiveForAssignment(?string $role = null): Collection
    {
        return Agent::query()
            ->where('status', AgentStatus::Active->value)
            ->when($role !== null, fn ($query) => $query->where('role', $role))
            ->orderBy('code')
            ->orderBy('id')
            ->get();
    }
}
