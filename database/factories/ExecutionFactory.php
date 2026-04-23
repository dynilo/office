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
            'idempotency_key' => fake()->unique()->uuid(),
            'status' => fake()->randomElement(ExecutionStatus::cases()),
            'attempt' => fake()->numberBetween(1, 3),
            'retry_count' => fake()->numberBetween(0, 2),
            'max_retries' => 2,
            'input_snapshot' => [
                'payload' => fake()->sentence(),
            ],
            'output_payload' => [
                'result' => fake()->paragraph(),
            ],
            'provider_response' => [
                'provider' => 'openai_compatible',
                'response_id' => fake()->uuid(),
            ],
            'error_message' => null,
            'failure_classification' => null,
            'started_at' => now()->subMinutes(fake()->numberBetween(1, 30)),
            'finished_at' => now()->subMinutes(fake()->numberBetween(0, 10)),
            'next_retry_at' => null,
        ];
    }
}
