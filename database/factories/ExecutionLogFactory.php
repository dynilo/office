<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ExecutionLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExecutionLog>
 */
class ExecutionLogFactory extends Factory
{
    protected $model = ExecutionLog::class;

    public function definition(): array
    {
        return [
            'execution_id' => Execution::factory(),
            'sequence' => fake()->unique()->numberBetween(1, 1000),
            'level' => fake()->randomElement(['info', 'warning', 'error']),
            'message' => fake()->sentence(),
            'context' => [
                'source' => 'worker',
            ],
            'logged_at' => now(),
        ];
    }
}
