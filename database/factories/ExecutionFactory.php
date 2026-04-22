<?php

namespace Database\Factories;

use App\Domain\Executions\Enums\ExecutionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Execution>
 */
class ExecutionFactory extends Factory
{
    protected $model = Execution::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'agent_id' => Agent::factory(),
            'status' => fake()->randomElement(ExecutionStatus::cases()),
            'attempt' => fake()->numberBetween(1, 3),
            'input_snapshot' => [
                'payload' => fake()->sentence(),
            ],
            'output_payload' => [
                'result' => fake()->paragraph(),
            ],
            'error_message' => null,
            'started_at' => now()->subMinutes(fake()->numberBetween(1, 30)),
            'finished_at' => now()->subMinutes(fake()->numberBetween(0, 10)),
        ];
    }
}
