<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\DeadLetterRecord;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeadLetterRecord>
 */
class DeadLetterRecordFactory extends Factory
{
    protected $model = DeadLetterRecord::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'agent_id' => Agent::factory(),
            'execution_id' => Execution::factory(),
            'reason_code' => fake()->randomElement(['expired_work', 'execution_timeout', 'provider_validation_failure']),
            'error_message' => fake()->sentence(),
            'payload' => [
                'source' => 'runtime',
            ],
            'captured_at' => now(),
        ];
    }
}
