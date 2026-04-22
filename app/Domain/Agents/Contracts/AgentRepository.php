<?php

namespace App\Domain\Agents\Contracts;

use App\Domain\Agents\Data\AgentIdentityData;

interface AgentRepository
{
    public function findIdentity(string $agentId): ?AgentIdentityData;

    public function exists(string $agentId): bool;
}
