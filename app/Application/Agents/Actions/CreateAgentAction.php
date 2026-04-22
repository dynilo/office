<?php

namespace App\Application\Agents\Actions;

use App\Domain\Agents\Enums\AgentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use Illuminate\Support\Facades\DB;

final class CreateAgentAction
{
    public function execute(array $attributes): Agent
    {
        return DB::transaction(function () use ($attributes): Agent {
            $agent = Agent::query()->create([
                'code' => $attributes['code'],
                'key' => $attributes['code'],
                'name' => $attributes['name'],
                'role' => $attributes['role'],
                'version' => '1.0.0',
                'status' => ($attributes['active'] ?? false) ? AgentStatus::Active : AgentStatus::Inactive,
                'capabilities' => $attributes['capabilities'],
            ]);

            $agent->profile()->create([
                'model_preference' => $attributes['model_preference'] ?? null,
                'temperature_policy' => $attributes['temperature_policy'] ?? null,
            ]);

            return $agent->load('profile');
        });
    }
}
