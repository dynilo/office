<?php

namespace App\Domain\Agents\Contracts;

use App\Domain\Agents\Data\AgentIdentityData;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use Illuminate\Support\Collection;

interface AgentRepository
{
    public function findIdentity(string $agentId): ?AgentIdentityData;

    public function exists(string $agentId): bool;

    /**
     * @return Collection<int, Agent>
     */
    public function findActiveForAssignment(?string $role = null): Collection;
}
