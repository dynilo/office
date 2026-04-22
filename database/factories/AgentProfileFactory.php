<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentProfile>
 */
class AgentProfileFactory extends Factory
{
    protected $model = AgentProfile::class;

    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'system_prompt' => fake()->paragraph(),
            'instructions' => [
                fake()->sentence(),
                fake()->sentence(),
            ],
            'defaults' => [
                'temperature' => 0.2,
                'response_format' => 'json',
            ],
            'metadata' => [
                'profile_version' => 1,
            ],
        ];
    }
}
