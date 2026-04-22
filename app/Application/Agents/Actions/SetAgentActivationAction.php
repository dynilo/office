<?php

namespace App\Application\Agents\Actions;

use App\Domain\Agents\Enums\AgentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;

final class SetAgentActivationAction
{
    public function activate(Agent $agent): Agent
    {
        $agent->update(['status' => AgentStatus::Active]);

        return $agent->refresh()->loadMissing('profile');
    }

    public function deactivate(Agent $agent): Agent
    {
        $agent->update(['status' => AgentStatus::Inactive]);

        return $agent->refresh()->loadMissing('profile');
    }
}
