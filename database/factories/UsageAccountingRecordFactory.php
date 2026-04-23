<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Infrastructure\Persistence\Eloquent\Models\UsageAccountingRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageAccountingRecord>
 */
class UsageAccountingRecordFactory extends Factory
{
    protected $model = UsageAccountingRecord::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'agent_id' => Agent::factory(),
            'task_id' => Task::factory(),
            'execution_id' => Execution::factory(),
            'metric_key' => fake()->randomElement([
                'tasks.created',
                'executions.created',
                'executions.succeeded',
                'executions.failed',
            ]),
            'quantity' => fake()->numberBetween(1, 5),
            'metadata' => [
                'source' => 'factory',
            ],
            'recorded_at' => now(),
        ];
    }
}
