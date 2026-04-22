<?php

namespace Database\Factories;

use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'title' => fake()->sentence(4),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'payload' => [
                'request' => fake()->paragraph(),
                'channel' => fake()->randomElement(['email', 'chat', 'api']),
            ],
            'context' => [
                'company_id' => fake()->uuid(),
            ],
            'submitted_at' => now()->subMinutes(fake()->numberBetween(1, 120)),
            'scheduled_at' => now()->addMinutes(fake()->numberBetween(1, 60)),
            'completed_at' => null,
        ];
    }
}
