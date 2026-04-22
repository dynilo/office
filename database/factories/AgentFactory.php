<?php

namespace Database\Factories;

use App\Domain\Agents\Enums\AgentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        $code = fake()->unique()->slug(2);

        return [
            'code' => $code,
            'key' => $code,
            'name' => fake()->unique()->company(),
            'role' => fake()->randomElement(['research', 'operations', 'support']),
            'version' => fake()->numerify('1.#.#'),
            'status' => fake()->randomElement(AgentStatus::cases()),
            'description' => fake()->sentence(),
            'capabilities' => fake()->randomElements([
                'ingestion',
                'routing',
                'analysis',
                'reporting',
            ], fake()->numberBetween(1, 3)),
            'metadata' => [
                'owner' => fake()->company(),
            ],
        ];
    }
}
