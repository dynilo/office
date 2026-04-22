<?php

namespace App\Application\Agents\Actions;

use App\Domain\Agents\Enums\AgentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use Illuminate\Support\Facades\DB;

final class UpdateAgentAction
{
    public function execute(Agent $agent, array $attributes): Agent
    {
        return DB::transaction(function () use ($agent, $attributes): Agent {
            $agent->fill([
                'code' => $attributes['code'],
                'key' => $attributes['code'],
                'name' => $attributes['name'],
                'role' => $attributes['role'],
                'status' => ($attributes['active'] ?? $agent->status->isOperational())
                    ? AgentStatus::Active
                    : AgentStatus::Inactive,
                'capabilities' => $attributes['capabilities'],
            ]);
            $agent->save();

            $agent->profile()->updateOrCreate(
                [],
                [
                    'model_preference' => $attributes['model_preference'] ?? null,
                    'temperature_policy' => $attributes['temperature_policy'] ?? null,
                ],
            );

            return $agent->load('profile');
        });
    }
}
